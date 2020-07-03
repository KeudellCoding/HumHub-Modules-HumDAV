<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\controllers;

use Yii;
use humhub\components\Response;
use humhub\components\Controller;

class RemoteController extends Controller {
    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false;

    /**
     * @inheritdoc
     */
    public function beforeAction($action) {
        Yii::$app->response->format = Response::FORMAT_RAW;
        $this->layout = false;
        return parent::beforeAction($action);
    }

    public function actionIndex() {
        return $this->render('index');
    }
}
