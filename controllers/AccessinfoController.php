<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\controllers;

use Yii;
use humhub\components\Controller;
use humhub\modules\humdav\components\MobileConfig;
use humhub\modules\humdav\models\UserSettingsEditForm;
use humhub\modules\humdav\models\UserToken;
use humhub\modules\humdav\models\UserTokenSearch;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\GoneHttpException;
use yii\web\ServerErrorHttpException;

class AccessinfoController extends Controller {
    /**
     * @inheritdoc
     */
    public function beforeAction($action) {
        $currentIdentity = Yii::$app->user->identity;
        if ($currentIdentity === null || Yii::$app->user->isGuest) {
            throw new ForbiddenHttpException('You\'re not signed in.');
        }
        
        $settings = Yii::$app->getModule('humdav')->settings;
        if ((bool)$settings->get('active', false) !== true) {
            throw new NotFoundHttpException('Module not activated');
        }
        
        $allowedUsers = array_filter((array)$settings->getSerialized('enabled_users'));
        if (!in_array($currentIdentity->guid, $allowedUsers) && !empty($allowedUsers)) {
            throw new ForbiddenHttpException('You\'re not allowed to enter this page.');
        }

        return parent::beforeAction($action);
    }

    public function actionIndex() {
        $userSettingsForm = new UserSettingsEditForm();
        if ($userSettingsForm->load(Yii::$app->request->post()) && $userSettingsForm->validate() && $userSettingsForm->save()) {
            $this->view->saved();
            return $this->redirect(Url::to(['/humdav/accessinfo/index']));
        }

        $searchModel = new UserTokenSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $calendar = Yii::$app->getModule('calendar');
        $calendarEnabled = $calendar !== null && $calendar->getIsEnabled();

        return $this->render('index', [
            'userSettingsModel' => $userSettingsForm,
            'enablePasswordAuth' => Yii::$app->getModule('humdav')->settings->get('enable_password_auth', false),
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'calendarEnabled' => $calendarEnabled,
            'calDavUrl' => $calendarEnabled ? Url::to(['/calendar/cal-dav/index'], true) : null,
        ]);
    }

    public function actionTokenInfo($id) {
        $userToken = UserToken::findOne($id);
        if ($userToken === null) throw new NotFoundHttpException();
        if (!$userToken->canEdit()) throw new ForbiddenHttpException();

        if ($userToken->load(Yii::$app->request->post()) && $userToken->validate() && $userToken->save()) {
            $this->view->saved();
            return $this->redirect(Url::to(['index']));
        }

        return $this->render('TokenInfo', ['userToken' => $userToken]);
    }

    public function actionRevokeToken($id) {
        $userToken = UserToken::findOne($id);
        if ($userToken === null) throw new NotFoundHttpException();
        if (!$userToken->canEdit()) throw new ForbiddenHttpException();

        if(Yii::$app->request->post('revoke-token-action') == 'revoke' && $userToken->delete()) {
            $this->view->info('The token has been revoked.');
            return $this->redirect(Url::to(['index']));
        }

        return $this->render('RevokeToken', ['userToken' => $userToken]);
    }

    public function actionGenerateToken() {
        if (Yii::$app->request->isPost) {
            $attributes = Yii::$app->session->getFlash('newCardDavToken', null, true);
            if (!is_array($attributes) || (int)($attributes['user_id'] ?? 0) !== (int)Yii::$app->user->id) {
                throw new GoneHttpException('This token form has expired. Please generate a new token.');
            }
            $newUserToken = new UserToken($attributes);
            
            if ($newUserToken->load(Yii::$app->request->post()) && $newUserToken->validate() && $newUserToken->save())
                $this->view->saved();
            else
                $this->view->info('The token could not be saved.');
            
            return $this->redirect(Url::to(['index']));
        }
        else if (Yii::$app->request->isGet) {
            $newUserToken = new UserToken();
            $newUserToken->user_id = Yii::$app->user->identity->id;
            $newUserToken->name = 'New Token';
            $token = $newUserToken->generateToken();
            if (empty($token)) throw new BadRequestHttpException('Cannot generate a token.');
            Yii::$app->session->setFlash('newCardDavToken', $newUserToken->getAttributes());

            return $this->render('GenerateToken', [
                'userToken' => $newUserToken,
                'token' => $token
            ]);
        }
        
        throw new BadRequestHttpException();
    }

    public function actionMobileconfig() {
        $target = Yii::$app->request->get('target');
        if (!in_array($target, ['ios', 'osx'], true)) {
            throw new BadRequestHttpException('Unsupported configuration profile target.');
        }

        $includeCalDav = Yii::$app->request->get('include_caldav', '1');
        if (!in_array($includeCalDav, ['0', '1'], true)) {
            throw new BadRequestHttpException('Invalid CalDAV configuration option.');
        }

        $user = Yii::$app->user->identity;
        $userToken = new UserToken();
        $userToken->user_id = $user->id;
        $userToken->name = 'Token for ' . $target;
        $token = $userToken->generateToken();
        if ($token === null) {
            throw new ServerErrorHttpException('Cannot generate a token.');
        }

        $content = MobileConfig::build($user, $token, $target, $includeCalDav === '1');
        if (!$userToken->save()) {
            throw new ServerErrorHttpException('Cannot save the configuration profile token.');
        }

        $response = Yii::$app->response->sendContentAsFile($content, 'humdav-profile.mobileconfig', [
            'mimeType' => 'application/x-apple-aspen-config',
        ]);
        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
