<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav;

use Yii;
use yii\web\JsonParser;
use humhub\modules\humdav\definitions\RouteDefinitions;

class Events {
    public static function onBeforeRequest($event) {
        if (substr(Yii::$app->request->pathInfo, 0, 7) != 'humdav/') {
            return;
        }

        Yii::$app->urlManager->addRules(RouteDefinitions::getDefinitions(), true);
    }
}
