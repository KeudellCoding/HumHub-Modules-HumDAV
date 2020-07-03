<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\controllers;

use Yii;
use yii\web\ForbiddenHttpException;
use humhub\components\Response;
use humhub\components\Controller;

class GenerateController extends Controller {
    /**
     * @inheritdoc
     */
    public function beforeAction($action) {
        Yii::$app->response->format = Response::FORMAT_RAW;
        $this->layout = false;

        $currentIdentity = Yii::$app->user->identity;
        if ($currentIdentity === null) {
            throw new ForbiddenHttpException('You\'re not signed in.');
        }

        return parent::beforeAction($action);
    }

    public function actionMobileconfig() {
        return $this->render('mobileconfig');
    }
}
