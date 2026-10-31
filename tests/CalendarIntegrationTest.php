<?php

use humhub\modules\humdav\components\MobileConfig;
use humhub\modules\calendar\helpers\AuthTokenService;
use humhub\modules\calendar\helpers\dav\CalDavAuth;
use humhub\modules\user\models\User;
use PHPUnit\Framework\TestCase;
use yii\helpers\Url;

final class CalendarIntegrationTest extends TestCase
{
    private $transaction;
    private User $reader;

    protected function setUp(): void
    {
        if (Yii::$app->getModule('calendar') === null) {
            self::markTestSkipped('Set HUMDAV_TEST_CALENDAR_PATH to test the optional Calendar integration.');
        }
        $this->transaction = Yii::$app->db->beginTransaction();
        $this->reader = User::find()->active()->one();
        Yii::$app->user->setIdentity($this->reader);
    }

    protected function tearDown(): void
    {
        if ($this->transaction !== null && $this->transaction->isActive) {
            $this->transaction->rollBack();
        }
        Yii::$app->user->setIdentity(User::find()->active()->one());
    }

    public function testMobileConfigIncludesCalDavWhenCalendarIsEnabled(): void
    {
        foreach (['ios', 'osx'] as $target) {
            $document = new DOMDocument();
            self::assertTrue($document->loadXML(MobileConfig::build($this->reader, 'carddav-token', $target)));
            $xpath = new DOMXPath($document);
            self::assertSame(1, $xpath->query('//key[.="CardDAVPassword"]')->length);
            self::assertSame('carddav-token', $xpath->evaluate('string(//key[.="CardDAVPassword"]/following-sibling::*[1])'));
            $password = $xpath->evaluate('string(//key[.="CalDAVPassword"]/following-sibling::*[1])');
            self::assertNotSame('carddav-token', $password);
            self::assertSame([$this->reader->id, $this->reader->username], AuthTokenService::instance()->calDavDecrypt($password));
            $expectedUrl = rtrim(Url::to(['/calendar/cal-dav/index'], $target === 'ios'), '/') . '/principals/' . rawurlencode($this->reader->username) . '/';
            self::assertSame($expectedUrl, $xpath->evaluate('string(//key[.="CalDAVPrincipalURL"]/following-sibling::*[1])'));
            self::assertNotSame($xpath->evaluate('string(//key[.="CardDAVPrincipalURL"]/following-sibling::*[1])'), $expectedUrl);
        }
    }

    public function testMobileConfigCanExcludeCalDavWhileKeepingCardDav(): void
    {
        foreach (['ios', 'osx'] as $target) {
            $document = new DOMDocument();
            self::assertTrue($document->loadXML(MobileConfig::build($this->reader, 'carddav-token', $target, false)));
            $xpath = new DOMXPath($document);
            self::assertSame(1, $xpath->query('/plist/dict/key[.="PayloadContent"]/following-sibling::array[1]/dict')->length);
            self::assertSame('carddav-token', $xpath->evaluate('string(//key[.="CardDAVPassword"]/following-sibling::*[1])'));
            self::assertSame(0, $xpath->query('//key[.="CalDAVPassword"]')->length);
        }
    }

    public function testMobileConfigTokenIsAcceptedByNativeCalendarAuthentication(): void
    {
        $document = new DOMDocument();
        $document->loadXML(MobileConfig::build($this->reader, 'carddav-token', 'ios'));
        $xpath = new DOMXPath($document);
        $password = $xpath->evaluate('string(//key[.="CalDAVPassword"]/following-sibling::*[1])');
        $auth = new CalDavAuth();
        self::assertSame($this->reader->id, $auth->auth($this->reader->username, $password)->id);
    }
}
