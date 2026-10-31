<?php

use humhub\modules\humdav\components\MobileConfig;
use humhub\modules\humdav\components\sabre\AuthenticationBackend;
use humhub\modules\humdav\definitions\VCardDefinitions;
use humhub\modules\humdav\models\UserToken;
use humhub\modules\humdav\models\UserTokenSearch;
use humhub\modules\humdav\models\UserSettingsEditForm;
use humhub\modules\humdav\models\admin\EditForm;
use humhub\modules\humdav\components\sabre\CardDavBackend;
use humhub\modules\user\models\User;
use PHPUnit\Framework\TestCase;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use Sabre\VObject\Reader;

final class CompatibilityTest extends TestCase
{
    private $transaction;
    private User $user;

    protected function setUp(): void
    {
        $this->transaction = Yii::$app->db->beginTransaction();
        $this->user = User::find()->active()->one();
        Yii::$app->user->setIdentity($this->user);
        $settings = Yii::$app->getModule('humdav')->settings;
        $settings->set('active', true);
        $settings->set('enable_password_auth', false);
        $settings->setSerialized('enabled_users', []);
    }

    protected function tearDown(): void
    {
        $this->transaction->rollBack();
    }

    private function token(): array
    {
        $model = new UserToken();
        $model->user_id = $this->user->id;
        $model->name = 'Integration test';
        $secret = $model->generateToken();
        self::assertTrue($model->save(), json_encode($model->getErrors()));
        return [$model, $secret];
    }

    public function testFormsCannotReplaceTokenOwnerOrHash(): void
    {
        [$model, $secret] = $this->token();
        $hash = $model->token;
        $salt = $model->salt;
        $algorithm = $model->algorithm;
        $model->load(['UserToken' => [
            'name' => 'Renamed token', 'user_id' => $this->user->id + 100,
            'token' => 'forged', 'salt' => 'forged', 'algorithm' => 'forged',
        ]]);
        self::assertSame($this->user->id, $model->user_id);
        self::assertSame($hash, $model->token);
        self::assertSame($salt, $model->salt);
        self::assertSame($algorithm, $model->algorithm);
        self::assertSame('Renamed token', $model->name);
        self::assertTrue($model->save());
        self::assertTrue(UserToken::validateTokenForUser($this->user, $secret));
    }

    private function otherUser(): User
    {
        $user = new User([
            'username' => 'humdav_other',
            'email' => 'humdav-other@example.test',
            'status' => User::STATUS_ENABLED,
            'language' => '',
        ]);
        self::assertTrue($user->save(), json_encode($user->getErrors()));
        return $user;
    }

    public function testTokensAreUserBoundAndRevocable(): void
    {
        [$model, $secret] = $this->token();
        self::assertFalse(UserToken::validateTokenForUser($this->otherUser(), $secret));
        self::assertFalse(UserToken::validateTokenForUser($this->user, 'incorrect'));
        self::assertTrue(UserToken::validateTokenForUser($this->user, $secret));
        $model->refresh();
        self::assertNotEmpty($model->last_time_used);
        self::assertNotSame($secret, $model->token);
        $model->delete();
        self::assertFalse(UserToken::validateTokenForUser($this->user, $secret));
    }

    public function testTokenSearchKeepsItsFiltersAndOwnerScope(): void
    {
        [$matching] = $this->token();
        $matching->name = 'Matching device';
        self::assertTrue($matching->save());
        [$otherName] = $this->token();
        [$otherOwner] = $this->token();
        $otherOwner->name = $matching->name;
        $otherOwner->user_id = $this->otherUser()->id;
        self::assertTrue($otherOwner->save());
        $search = new UserTokenSearch();
        $provider = $search->search(['UserTokenSearch' => [
            'name' => 'Matching device',
            'user_id' => $otherOwner->user_id,
        ]]);
        $ids = array_column($provider->getModels(), 'id');
        self::assertSame([$matching->id], $ids);
    }

    public function testAllowedUsersSettingAppliesToTokenAuthentication(): void
    {
        [, $secret] = $this->token();
        $settings = Yii::$app->getModule('humdav')->settings;
        $settings->setSerialized('enabled_users', ['unrelated-user-guid']);
        self::assertFalse(UserToken::validateTokenForUser($this->user, $secret));
        $settings->setSerialized('enabled_users', [$this->user->guid]);
        self::assertTrue(UserToken::validateTokenForUser($this->user, $secret));
    }

    public function testSettingsFormsPersistGlobalAndUserPreferences(): void
    {
        $form = new EditForm();
        $form->load(['EditForm' => [
            'active' => true,
            'instruction_location' => 'top_menu',
            'instruction_location_sort_order' => 123,
            'enabled_users' => [$this->user->guid],
            'include_address' => false,
        ]]);
        self::assertTrue($form->validate(), json_encode($form->getErrors()));
        self::assertTrue($form->save());
        $saved = new EditForm();
        self::assertSame('top_menu', $saved->instruction_location);
        self::assertSame(123, (int)$saved->instruction_location_sort_order);
        self::assertSame([$this->user->guid], $saved->enabled_users);
        self::assertFalse((bool)$saved->include_address);

        $userForm = new UserSettingsEditForm();
        $userForm->provide_groups_as_separate_addressbooks = true;
        self::assertTrue($userForm->validate());
        self::assertTrue($userForm->save());
        $books = (new CardDavBackend())->getAddressBooksForUser('principals/' . $this->user->guid);
        self::assertContains('following', array_column($books, 'uri'));
    }

    public function testDavAuthenticationSetsAnIdentityWithoutASession(): void
    {
        [, $secret] = $this->token();
        Yii::$app->user->setIdentity(null);
        $backend = new AuthenticationBackend();
        $request = new Request('PROPFIND', '/humdav/remote/', [
            'Authorization' => 'Basic ' . base64_encode($this->user->username . ':' . $secret),
        ]);
        [$success, $principal] = $backend->check($request, new Response());
        self::assertTrue($success);
        self::assertSame('principals/' . $this->user->guid, $principal);
        self::assertSame($this->user->id, Yii::$app->user->id);
        self::assertFalse(Yii::$app->user->enableSession);
    }

    public function testVCard40SupportsBirthdaysWithoutAYear(): void
    {
        $this->user->profile->birthday = '1980-02-29';
        $this->user->profile->birthday_hide_year = 1;
        $card = VCardDefinitions::getVCard($this->user, $this->user);
        self::assertNotNull($card);
        $parsed = Reader::read($card);
        self::assertSame('4.0', (string)$parsed->VERSION);
        self::assertSame('--0229', (string)$parsed->BDAY);
        self::assertSame($this->user->displayName, (string)$parsed->FN);
        self::assertSame([], $parsed->validate());
    }

    public function testMobileConfigEscapesXmlAndWorksWithoutCalendar(): void
    {
        $calendar = Yii::$app->getModule('calendar');
        Yii::$app->setModule('calendar', null);
        try {
            Yii::$app->settings->set('name', 'Müller & <Friends>');
            foreach (['ios', 'osx'] as $target) {
                $xml = MobileConfig::build($this->user, 'token<&"', $target);
                $document = new DOMDocument();
                self::assertTrue($document->loadXML($xml));
                $xpath = new DOMXPath($document);
                self::assertSame('Müller & <Friends> HumDAV Configuration', $xpath->evaluate('string(/plist/dict/key[.="PayloadDisplayName"]/following-sibling::*[1])'));
                self::assertSame('token<&"', $xpath->evaluate('string(//key[.="CardDAVPassword"]/following-sibling::*[1])'));
                self::assertSame('8100', $xpath->evaluate('string(//key[.="CardDAVPort"]/following-sibling::*[1])'));
                self::assertSame(0, $xpath->query('//key[.="CalDAVPassword"]')->length);
            }
        } finally {
            Yii::$app->setModule('calendar', $calendar);
        }
    }

    public function testUnsupportedMobileConfigTargetIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MobileConfig::build($this->user, 'token', 'unsupported');
    }
}
