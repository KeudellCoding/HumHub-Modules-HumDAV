<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\controllers;

use Yii;
use yii\helpers\Url;
use humhub\components\Response;
use humhub\modules\admin\components\Controller;
use humhub\modules\humdav\models\admin\EditForm;

class AdminController extends Controller {
    public function actionIndex() {
        Yii::$app->response->format = Response::FORMAT_HTML;

        $form = new EditForm();
        
        if ($form->load(Yii::$app->request->post()) && $form->validate() && $form->save()) {
            $this->view->saved();
            return $this->redirect(Url::to(['/humdav/admin/index']));
        }

        $versionInfos = [
            'local_version' => Yii::$app->getModule('usermap')->version,
            'github_version' => $this->getGitHubVersion()
        ];

        return $this->render('index', ['model' => $form, 'version_infos' => $versionInfos]);
    }

    public function actionUpdate() {
        exec('git', $gitAvailable);
        if (!empty($gitAvailable)) {
            exec('cd '.__DIR__.' && cd .. && git pull origin master');
        }
        return $this->redirect(Url::to(['/humdav/admin/index']));
    }

    private function getGitHubVersion() {
        $rawConfigJson = file_get_contents('https://raw.githubusercontent.com/KeudellCoding/HumHub-Modules-HumDAV/master/module.json');
        if (!empty($rawConfigJson)) {
            $configJson = json_decode($rawConfigJson, true);
            return $configJson['version'];
        }
        return null;
    }
}
