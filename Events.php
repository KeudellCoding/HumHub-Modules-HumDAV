<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav;

use Yii;
use yii\helpers\Url;
use yii\web\JsonParser;
use humhub\modules\humdav\definitions\RouteDefinitions;

class Events {
    public static function onBeforeRequest($event) {
        if (substr(Yii::$app->request->pathInfo, 0, 7) != 'humdav/') {
            return;
        }

        Yii::$app->urlManager->addRules(RouteDefinitions::getDefinitions(), true);
    }

    public static function onDirectoryMenuInit($event) {
        try {
            $currentIdentity = Yii::$app->user->identity;
            if ($currentIdentity === null) {
                return;
            }
            
            $settings = Yii::$app->getModule('humdav')->settings;
            if ((boolean)$settings->get('active', false) !== true) {
                return;
            }
            
            $allowedUsers = array_filter((array)$settings->getSerialized('enabled_users'));
            if (!in_array($currentIdentity->guid, $allowedUsers) && !empty($allowedUsers)) {
                return;
            }
            
            $event->sender->addItem([
                'label' => 'HumDAV',
                'url' => Url::to(['/humdav/accessinfos']),
                'group' => 'directory',
                'htmlOptions' => [],
                'icon' => '<i class="fa far fa-address-card"></i>',
                'isActive' => (Yii::$app->controller->module
                    && Yii::$app->controller->module->id === 'humdav'
                    && Yii::$app->controller->action->id === 'accessinfos'),
                'sortOrder' => 1100,
            ]);
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }
}
