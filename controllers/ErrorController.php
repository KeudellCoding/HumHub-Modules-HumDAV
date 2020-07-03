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
use humhub\components\Controller;

class ErrorController extends Controller {
    public function actionNotfound() {
        throw new NotFoundHttpException('The requested page does not exist.');
    }
}