<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\controllers;

use Yii;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use humhub\components\Response;
use humhub\components\Controller;
use humhub\modules\humdav\components\SecurityZoneController;

class GenerateController extends SecurityZoneController {
    /**
     * @inheritdoc
     */
    public function beforeAction($action) {
        return parent::beforeAction($action);
        
        Yii::$app->response->format = Response::FORMAT_RAW;
        $this->layout = false;
    }

    public function actionMobileconfig() {
        return $this->render('mobileconfig');
    }
}
