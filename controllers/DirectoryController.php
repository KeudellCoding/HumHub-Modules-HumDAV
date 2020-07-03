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

class DirectoryController extends SecurityZoneController {
    public function actionAccessinfos() {
        return $this->render('accessinfos');
    }
}
