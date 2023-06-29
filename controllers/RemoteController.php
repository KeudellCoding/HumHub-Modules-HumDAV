<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\controllers;

require_once __DIR__.'/../vendor/autoload.php';

use Yii;
use humhub\components\Response;
use humhub\components\Controller;
use humhub\components\access\ControllerAccess;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\humdav\components\sabre\AppleQuirksPlugin;
use humhub\modules\humdav\components\sabre\PrincipalBackend;
use humhub\modules\humdav\components\sabre\CardDavBackend;
use humhub\modules\humdav\components\sabre\CalDavBackend;
use humhub\modules\humdav\components\sabre\AuthenticationBackend;
use humhub\modules\humdav\definitions\VCalDefinitions;
use humhub\modules\humdav\models\sabre\AddressBookRoot;
use humhub\modules\humdav\models\sabre\CalendarRoot;
use humhub\modules\humdav\models\sabre\PrincipalCollection;
use humhub\modules\humdav\models\UserToken;
use humhub\modules\user\models\User;
use yii\web\HttpException;
use Sabre\DAV\Server;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class RemoteController extends Controller {
    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false;

    /**
     * @inerhitdoc
     * Do not enforce authentication.
     */
    public $access = ControllerAccess::class;

    /**
     * @inheritdoc
     */
    public function beforeAction($action) {
        Yii::$app->response->clear();
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->request->setBodyParams(null);
        $this->layout = false;
        
        $settings = Yii::$app->getModule('humdav')->settings;
        if ((boolean)$settings->get('active', false) !== true) {
            throw new HttpException(403, 'Module not activated');
        }
        
        return true;
    }

    public function actionIndex() {
        $settings = Yii::$app->getModule('humdav')->settings;

        //Backends
        $principalBackend = new PrincipalBackend();
        $cardDavBackend = new CardDavBackend();
        $calDavBackend = new CalDavBackend();
        $authBackend = new AuthenticationBackend();

        // Setting up the directory tree
        $nodes = [
            new PrincipalCollection($principalBackend),
            new AddressBookRoot($principalBackend, $cardDavBackend),
            new CalendarRoot($principalBackend, $calDavBackend)
        ];


        // The object tree needs in turn to be passed to the server class
        $server = new Server($nodes);
        $server->setBaseUri('/humdav/remote');

        // Plugins
        $server->addPlugin(new AppleQuirksPlugin());

        $server->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend));

        $aclPlugin = new \Sabre\DAVACL\Plugin();
        $aclPlugin->allowUnauthenticatedAccess = false;
        $aclPlugin->hideNodesFromListings = true;
        $server->addPlugin($aclPlugin);

        $server->addPlugin(new \Sabre\CardDAV\Plugin());
        $server->addPlugin(new \Sabre\CalDAV\Plugin());

        if ((boolean)$settings->get('enable_browser_plugin', false) === true) {
            $server->addPlugin(new \Sabre\DAV\Browser\Plugin());
        }
        
        $server->start();
        exit(0);
    }

    public function actionIcal(string $userGuid, string $calendarId, string $token) {
        if (Yii::$app->getModule('calendar') === null) throw new BadRequestHttpException("Calendar Module not enabled");

        $user = User::findOne(['guid' => $userGuid, 'status' => User::STATUS_ENABLED]);
        if ($user === null) throw new ForbiddenHttpException("User or Token was invalid");
        
        if (!UserToken::validateTokenForUser($user, $token, UserToken::USED_FOR_ICAL)) throw new ForbiddenHttpException("User or Token was invalid");

        $calendarEntries = VCalDefinitions::getCalendarObjects($calendarId, $user);
        if ($calendarEntries === null) throw new NotFoundHttpException("Calendar not found");

        $ical = VCalDefinitions::getICalFormat($calendarEntries);
        return Yii::$app->response->sendContentAsFile($ical, uniqid() . '.ics', ['mimeType' => 'text/calendar']);
    }
}
