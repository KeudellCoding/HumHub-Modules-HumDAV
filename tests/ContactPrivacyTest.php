<?php

/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

use humhub\libs\BasePermission;
use humhub\modules\humdav\components\sabre\CardDavBackend;
use humhub\modules\humdav\components\sabre\PrincipalBackend;
use humhub\modules\humdav\definitions\VCardDefinitions;
use humhub\modules\humdav\models\admin\EditForm;
use humhub\modules\humdav\models\sabre\AddressBookRoot;
use humhub\modules\humdav\models\sabre\Principal;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\fieldtype\UserEmail;
use humhub\modules\user\models\Follow;
use humhub\modules\user\models\Profile;
use humhub\modules\user\models\ProfileField;
use humhub\modules\user\models\ProfileFieldCategory;
use humhub\modules\user\models\User;
use humhub\modules\user\permissions\ViewAboutPage;
use PHPUnit\Framework\TestCase;
use Sabre\DAV\Exception\NotFound;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Reader;

final class ContactPrivacyTest extends TestCase
{
    private $transaction;
    private User $administrator;
    private User $viewer;
    private User $target;
    private ProfileField $emailField;

    protected function setUp(): void
    {
        $this->transaction = Yii::$app->db->beginTransaction();
        $this->administrator = User::find()->active()->one();
        $this->viewer = $this->user('humdav_viewer');
        $this->target = $this->user('humdav_contact');
        Yii::$app->user->setIdentity($this->viewer);
        $profile = $this->target->profile ?? new Profile(['user_id' => $this->target->id]);
        $profile->setAttributes([
            'firstname' => 'Private', 'lastname' => 'Contact',
            'street' => 'Hidden Street 42', 'city' => 'Visible City', 'zip' => '12345',
            'country' => 'DE', 'state' => 'Test Region', 'gender' => 'female',
            'birthday' => '1980-02-29', 'birthday_hide_year' => 1,
            'phone_private' => '+49 123456', 'phone_work' => '+49 987654',
            'mobile' => '+49 55555', 'fax' => '+49 66666',
            'url' => 'https://example.test/private-contact',
        ], false);
        self::assertTrue($profile->save(false));
        $this->target->populateRelation('profile', $profile);
        ProfileField::updateAll(['visible' => 1], ['internal_name' => array_keys($profile->getAttributes())]);
        ProfileField::updateAll(['visible' => 0], ['field_type_class' => UserEmail::class]);
        $this->emailField = new ProfileField([
            'internal_name' => 'humdav_email_test',
            'title' => 'Test email',
            'profile_field_category_id' => ProfileFieldCategory::find()->select('id')->scalar(),
            'field_type_class' => UserEmail::class,
            'visible' => 1,
            'sort_order' => 99,
        ]);
        self::assertTrue($this->emailField->save(), json_encode($this->emailField->getErrors()));
        $settings = Yii::$app->getModule('humdav')->settings;
        foreach (['email', 'address', 'profile_image', 'birthday', 'gender', 'phone_numbers', 'url'] as $option) {
            $settings->set('include_' . $option, true);
        }
        $this->allowAbout(true);
    }

    protected function tearDown(): void
    {
        $this->transaction->rollBack();
        Yii::$app->runtimeCache->flush();
        Yii::$app->cache->flush();
        Yii::$app->user->setIdentity($this->administrator);
    }

    private function user(string $name): User
    {
        $user = new User([
            'username' => $name,
            'email' => $name . '@example.test',
            'status' => User::STATUS_ENABLED,
            'language' => '',
        ]);
        self::assertTrue($user->save(), json_encode($user->getErrors()));
        return $user;
    }

    private function allowAbout(bool $allowed): void
    {
        $this->target->getPermissionManager($this->viewer)->setGroupState(
            User::USERGROUP_USER,
            ViewAboutPage::class,
            $allowed ? BasePermission::STATE_ALLOW : BasePermission::STATE_DENY,
        );
    }

    private function card(?User $viewer = null): VCard
    {
        $value = VCardDefinitions::getVCard($this->target, $viewer ?? $this->viewer);
        self::assertNotNull($value);
        return Reader::read($value);
    }

    public function testDeniedAboutPageExportsNoPersonalFieldsEvenWithAdministratorSession(): void
    {
        $this->allowAbout(false);
        Yii::$app->user->setIdentity($this->administrator);
        $card = $this->card();
        self::assertSame($this->target->displayName, (string)$card->FN);
        foreach (['N', 'EMAIL', 'ADR', 'TEL', 'BDAY', 'GENDER', 'URL', 'PHOTO'] as $property) {
            self::assertFalse(isset($card->{$property}), $property . ' must require About access.');
        }
        self::assertSame($this->administrator->id, Yii::$app->user->id);
    }

    public function testFieldVisibilityRedactsIndividualAddressAndPhoneComponents(): void
    {
        ProfileField::updateAll(['visible' => 0], ['internal_name' => [
            'firstname', 'street', 'phone_private', 'birthday', 'gender', 'url',
        ]]);
        $card = $this->card();
        self::assertSame('', $card->N->getParts()[1]);
        self::assertSame('', $card->ADR->getParts()[2]);
        self::assertSame('Visible City', $card->ADR->getParts()[3]);
        $phones = array_map(static fn($phone) => (string)$phone, $card->select('TEL'));
        self::assertNotContains('+49123456', $phones);
        self::assertContains('+49987654', $phones);
        foreach (['BDAY', 'GENDER', 'URL'] as $property) {
            self::assertFalse(isset($card->{$property}));
        }
    }

    public function testEmailRequiresOptInVisibleVirtualFieldAndAboutAccess(): void
    {
        $settings = Yii::$app->getModule('humdav')->settings;
        $settings->set('include_email', false);
        self::assertFalse(isset($this->card()->EMAIL));
        $settings->set('include_email', true);
        self::assertSame($this->target->email, (string)$this->card()->EMAIL);
        $this->emailField->updateAttributes(['visible' => 0]);
        self::assertFalse(isset($this->card()->EMAIL));
        $this->emailField->updateAttributes(['visible' => 1]);
        $this->allowAbout(false);
        self::assertFalse(isset($this->card()->EMAIL));
    }

    public function testOwnerCanExportVisibleFieldsAndBirthdayYearStaysHidden(): void
    {
        $this->allowAbout(false);
        $card = $this->card($this->target);
        self::assertSame('--0229', (string)$card->BDAY);
        self::assertSame($this->target->email, (string)$card->EMAIL);
        self::assertSame([], $card->validate());
    }

    public function testOptionalFieldsAreOffWhenNoAdministratorChoiceExists(): void
    {
        $settings = Yii::$app->getModule('humdav')->settings;
        $options = ['email', 'address', 'profile_image', 'birthday', 'gender', 'phone_numbers', 'url'];
        foreach ($options as $option) {
            $settings->delete('include_' . $option);
        }
        $form = new EditForm();
        foreach ($options as $option) {
            self::assertFalse((bool)$form->{'include_' . $option});
        }
        $card = $this->card();
        foreach (['EMAIL', 'ADR', 'TEL', 'BDAY', 'GENDER', 'URL', 'PHOTO'] as $property) {
            self::assertFalse(isset($card->{$property}));
        }
    }

    public function testSingleAndMultipleCardReadsApplyPrivacyWithoutEnumeratingTheBook(): void
    {
        $this->allowAbout(false);
        $backend = new class extends CardDavBackend {
            public function getCards($addressBookId)
            {
                throw new RuntimeException('Single and multiget reads must not rebuild every contact.');
            }
        };
        $uri = $this->target->guid . '.vcf';
        $single = $backend->getCard('0', $uri);
        self::assertSame($uri, $single['uri']);
        self::assertFalse(isset(Reader::read($single['carddata'])->EMAIL));
        self::assertFalse(isset(Reader::read($single['carddata'])->TEL));
        self::assertSame([$single], $backend->getMultipleCards('0', [$uri, 'missing.vcf', $uri]));
        self::assertFalse($backend->getCard('0', 'missing.vcf'));
        self::assertFalse($backend->getCard('1', $uri));
        self::assertFalse($backend->getCard('space_999999', $uri));
    }

    public function testPrincipalsAndAddressBooksBelongToTheAuthenticatedUser(): void
    {
        $principals = new PrincipalBackend();
        $own = $principals->getPrincipalByPath('principals/' . $this->viewer->guid);
        self::assertNotNull($own);
        self::assertSame($this->viewer->email, $own['{http://sabredav.org/ns}email-address']);
        self::assertNull($principals->getPrincipalByPath('principals/' . $this->target->guid));
        self::assertCount(1, $principals->getPrincipalsByPrefix('principals'));
        $backend = new CardDavBackend();
        self::assertSame([], $backend->getAddressBooksForUser('principals/' . $this->target->guid));
        $root = new AddressBookRoot($principals, $backend);
        $this->expectException(NotFound::class);
        $root->getChildForPrincipal(['uri' => 'principals/' . $this->target->guid]);
    }

    public function testOwnPrincipalEmailFollowsTheVCardExportPolicy(): void
    {
        Yii::$app->user->setIdentity($this->target);
        $backend = new PrincipalBackend();
        $settings = Yii::$app->getModule('humdav')->settings;
        foreach ([
            [true, true, true],
            [false, true, false],
            [true, false, false],
        ] as [$enabled, $visible, $expected]) {
            $settings->set('include_email', $enabled);
            $this->emailField->updateAttributes(['visible' => (int)$visible]);
            $principal = $backend->getPrincipalByPath('principals/' . $this->target->guid);
            self::assertNotNull($principal);
            if ($expected) {
                self::assertSame($this->target->email, $principal['{http://sabredav.org/ns}email-address']);
            } else {
                self::assertArrayNotHasKey('{http://sabredav.org/ns}email-address', $principal);
            }
            self::assertSame([$principal], $backend->getPrincipalsByPrefix('principals'));
            $node = new Principal($backend, $principal);
            self::assertSame($expected ? ['mailto:' . $this->target->email] : [], $node->getAlternateUriSet());
            $card = $this->card($this->target);
            self::assertSame($expected, isset($card->EMAIL));
        }

        $settings->set('include_email', true);
        $this->emailField->updateAttributes(['visible' => 1]);
        $this->target->email = '';
        $principal = $backend->getPrincipalByPath('principals/' . $this->target->guid);
        self::assertArrayNotHasKey('{http://sabredav.org/ns}email-address', $principal);
        self::assertNull($backend->getPrincipalByPath('principals/' . $this->viewer->guid));
    }

    public function testFollowingAndSpaceReadsStayWithinTheirCollections(): void
    {
        $settings = Yii::$app->getModule('humdav')->settings;
        $settings->set('enable_space_addressbooks', true);
        $settings->contentContainer($this->viewer)->set('provide_groups_as_separate_addressbooks', true);
        $backend = new CardDavBackend();
        $uri = $this->target->guid . '.vcf';
        self::assertFalse($backend->getCard('1', $uri));
        $follow = new Follow([
            'user_id' => $this->viewer->id,
            'object_model' => User::class,
            'object_id' => $this->target->id,
        ]);
        self::assertTrue($follow->save(), json_encode($follow->getErrors()));
        self::assertSame($uri, $backend->getCard('1', $uri)['uri']);
        self::assertSame([$uri], array_column($backend->getMultipleCards('1', [$uri, $this->viewer->guid . '.vcf']), 'uri'));

        $space = new Space(['name' => 'HumDAV scoped contacts']);
        self::assertTrue($space->save(), json_encode($space->getErrors()));
        $book = 'space_' . $space->id;
        self::assertFalse($backend->getCard($book, $uri));
        self::assertTrue($space->addMember($this->target->id));
        self::assertSame($uri, $backend->getCard($book, $uri)['uri']);
        $settings->set('enable_space_addressbooks', false);
        self::assertFalse($backend->getCard($book, $uri));
    }

    public function testHiddenAndInactiveUsersAreExcludedFromEveryLookup(): void
    {
        $backend = new CardDavBackend();
        $uri = $this->target->guid . '.vcf';
        self::assertNotFalse($backend->getCard('0', $uri));
        foreach ([
            ['visibility' => User::VISIBILITY_HIDDEN],
            ['visibility' => User::VISIBILITY_ALL, 'status' => User::STATUS_DISABLED],
        ] as $attributes) {
            $this->target->updateAttributes($attributes);
            self::assertFalse($backend->getCard('0', $uri));
            self::assertSame([], $backend->getMultipleCards('0', [$uri]));
            self::assertNotContains($uri, array_column($backend->getCards('0'), 'uri'));
        }
    }
}
