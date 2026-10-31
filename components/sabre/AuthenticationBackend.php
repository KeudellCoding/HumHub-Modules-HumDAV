<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\components\sabre;

use humhub\modules\humdav\models\UserToken;
use Sabre\DAV\Auth\Backend\AbstractBasic;
use humhub\modules\user\models\User;
use Sabre\HTTP\Auth\Basic;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Yii;

class AuthenticationBackend extends AbstractBasic {
    function check(RequestInterface $request, ResponseInterface $response) {
        $auth = new Basic(
            $this->realm,
            $request,
            $response
        );

        $userpass = $auth->getCredentials();
        if (!$userpass)
            return [false, "No 'Authorization: Basic' header found. Either the client didn't send one, or the server is misconfigured"];

        $user = User::findOne(['username' => $userpass[0], 'status' => User::STATUS_ENABLED]);
        if ($user === null)
            return [false, "Username or password was incorrect"];

        $settings = Yii::$app->getModule('humdav')->settings;
        if ((bool)$settings->get('active', false) !== true)
            return [false, "Username or password was incorrect"];
            
        $allowedUsers = array_filter((array)$settings->getSerialized('enabled_users'));
        if (!in_array($user->guid, $allowedUsers) && !empty($allowedUsers))
            return [false, "Username or password was incorrect"];

        if (!$this->validateUserPass($userpass[0], $userpass[1]))
            return [false, "Username or password was incorrect"];

        Yii::$app->user->setIdentity($user);

        return [true, $this->principalPrefix . $user->guid];
    }

    protected function validateUserPass($username, $password) {
        $user = User::findOne(['username' => $username, 'status' => User::STATUS_ENABLED]);
        if ($user === null) return false;
        
        if (UserToken::validateTokenForUser($user, $password)) {
            return true;
        }

        $settings = Yii::$app->getModule('humdav')->settings;
        if ($settings->get('enable_password_auth', false)) {
            if ($user->currentPassword !== null && $user->currentPassword->validatePassword($password)) {
                return true;
            }
        }

        return false;
    }
}

