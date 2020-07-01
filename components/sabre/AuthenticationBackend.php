<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\components\sabre;

use Yii;
use yii\web\ForbiddenHttpException;
use Sabre\DAV\Auth\Backend\BackendInterface;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use humhub\modules\user\models\User;
use humhub\modules\user\models\forms\Login;
use humhub\modules\user\authclient\AuthClientHelpers;

class AuthenticationBackend implements BackendInterface {
    /**
     * @inheritdoc
     */
    public function check(RequestInterface $request, ResponseInterface $response) {
        $settings = Yii::$app->getModule('humdav')->settings;
        if ((boolean)$settings->get('active', false) !== true) {
            return [false, 'Module not activated'];
        }

        list($username, $password) = Yii::$app->request->getAuthCredentials();
        $user = self::getUserByCredentials($username, $password);
        if ($user === null) {
            return [false, 'Not logged in'];
        }

        //Optional: Check if User is allowed
        
        return [true, 'principals/'.$username];
    }

    /**
     * @inheritdoc
     */
    public function challenge(RequestInterface $request, ResponseInterface $response) {
        header('WWW-Authenticate: Basic realm="HumDAV"');
        header('HTTP/1.0 401 Unauthorized');
        die;
    }

    public static function getUserByCredentials($username, $password) {
        $login = new Login;
        if (!$login->load(['username' => $username, 'password' => $password], '') || !$login->validate()) {
            return null;
        }

        $user = AuthClientHelpers::getUserByAuthClient($login->authClient);
        return $user;
    }
}