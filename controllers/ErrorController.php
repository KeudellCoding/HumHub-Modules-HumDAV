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
use humhub\modules\humdav\components\BaseController;

class ErrorController extends BaseController {
    public function actionNotfound() {
        throw new NotFoundHttpException('The requested page does not exist.'.$_SERVER['REQUEST_URI']);
    }
}