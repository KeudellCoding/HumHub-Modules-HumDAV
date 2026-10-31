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
use humhub\modules\humdav\components\sabre\PrincipalBackend;
use humhub\modules\humdav\components\sabre\CardDavBackend;
use humhub\modules\humdav\components\sabre\AuthenticationBackend;
use humhub\modules\humdav\models\sabre\AddressBookRoot;
use humhub\modules\humdav\models\sabre\PrincipalCollection;
use yii\web\HttpException;
use Sabre\DAV\Server;
use yii\helpers\Url;

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
        Yii::$app->request->setBodyParams([]);
        Yii::$app->user->enableSession = false;
        Yii::$app->user->enableAutoLogin = false;
        Yii::$app->user->setIdentity(null);
        $this->layout = false;
        
        $settings = Yii::$app->getModule('humdav')->settings;
        if ((bool)$settings->get('active', false) !== true) {
            throw new HttpException(403, 'Module not activated');
        }
        
        return parent::beforeAction($action);
    }

    public function actionIndex() {
        $settings = Yii::$app->getModule('humdav')->settings;

        //Backends
        $principalBackend = new PrincipalBackend();
        $cardDavBackend = new CardDavBackend();
        $authBackend = new AuthenticationBackend();

        // Setting up the directory tree
        $nodes = [
            new PrincipalCollection($principalBackend),
            new AddressBookRoot($principalBackend, $cardDavBackend)
        ];


        // The object tree needs in turn to be passed to the server class
        $server = new Server($nodes);
        $server->setBaseUri(rtrim(Url::to(['/humdav/remote']), '/') . '/');

        // Plugins
        $server->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend));

        $aclPlugin = new \Sabre\DAVACL\Plugin();
        $aclPlugin->allowUnauthenticatedAccess = false;
        $aclPlugin->hideNodesFromListings = true;
        $server->addPlugin($aclPlugin);

        $server->addPlugin(new \Sabre\CardDAV\Plugin());

        if ((bool)$settings->get('enable_browser_plugin', false) === true) {
            $server->addPlugin(new \Sabre\DAV\Browser\Plugin());
        }
        
        $server->start();
        exit(0);
    }

    public function actionDiscovery() {
        return $this->redirect(rtrim(Url::to(['/humdav/remote']), '/') . '/', 301);
    }

}
