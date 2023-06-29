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
use humhub\components\Response;
use humhub\modules\humdav\models\UserSettingsEditForm;
use humhub\modules\humdav\models\UserToken;
use humhub\modules\humdav\models\UserTokenSearch;
use humhub\modules\space\models\Membership;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\GoneHttpException;

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
        if ((boolean)$settings->get('active', false) !== true) {
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

        $iCalCalendars = [];
        if (Yii::$app->getModule('calendar') !== null) {
            if (Yii::$app->user->identity->moduleManager->isEnabled('calendar'))
                $iCalCalendars['0'] = 'Personal';
            foreach (Membership::getUserSpaces(Yii::$app->user->identity->id) as $space)
                $iCalCalendars['space_'.$space->id] = 'Space: '.$space->name;
        }

        return $this->render('index', [
            'userSettingsModel' => $userSettingsForm,
            'enablePasswordAuth' => Yii::$app->getModule('humdav')->settings->get('enable_password_auth', false),
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'iCalCalendars' => $iCalCalendars,
            'calendarActivated' => Yii::$app->getModule('calendar') !== null && Yii::$app->getModule('calendar')->isActivated
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
            $newUserToken = new UserToken(Json::decode(Yii::$app->session->getFlash('newUserToken', '{}', true)));
            if ($newUserToken == null) throw new GoneHttpException();
            
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
            Yii::$app->session->setFlash('newUserToken', Json::encode($newUserToken));

            return $this->render('GenerateToken', [
                'userToken' => $newUserToken,
                'token' => $token
            ]);
        }
        
        throw new BadRequestHttpException();
    }

    public function actionMobileconfig() {
        Yii::$app->response->format = Response::FORMAT_RAW;
        $this->layout = false;
        
        return $this->render('MobileConfig');
    }
}
