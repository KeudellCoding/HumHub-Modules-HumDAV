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
use yii\helpers\StringHelper;
use humhub\modules\humdav\definitions\RouteDefinitions;
use humhub\modules\humdav\models\UserToken;
use humhub\modules\ui\menu\MenuLink;
use humhub\modules\user\models\User;

class Events {
    public static function onBeforeRequest($event) {
        if (!StringHelper::startsWith(Yii::$app->request->pathInfo, 'humdav/')
            && Yii::$app->request->pathInfo !== '.well-known/carddav') {
            return;
        }

        Yii::$app->urlManager->addRules(RouteDefinitions::getDefinitions(), true);
    }
    
    public static function onTopMenuInit($event) {
        try {
            $settings = Yii::$app->getModule('humdav')->settings;
            if ((bool)$settings->get('active', false) !== true) {
                return;
            }
            if ($settings->get('instruction_location') !== 'top_menu') {
                return;
            }

            $currentIdentity = Yii::$app->user->identity;
            if ($currentIdentity === null || Yii::$app->user->isGuest) {
                return;
            }
            $allowedUsers = array_filter((array)$settings->getSerialized('enabled_users'));
            if (!in_array($currentIdentity->guid, $allowedUsers) && !empty($allowedUsers)) {
                return;
            }
            
            $event->sender->addEntry(new MenuLink([
                'label' => 'HumDAV',
                'url' => Url::to(['/humdav/accessinfo/index']),
                'htmlOptions' => [],
                'icon' => 'address-card',
                'isActive' => (Yii::$app->controller->module
                    && Yii::$app->controller->module->id === 'humdav'
                    && Yii::$app->controller->id === 'accessinfo'),
                'sortOrder' => $settings->get('instruction_location_sort_order', 400),
            ]));
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    public static function onAfterUpdate($event) {
        if ($event->moduleId === 'humdav'){
            try {
                $settings = Yii::$app->getModule('humdav')->settings;
                if ($settings->get('instruction_location') === 'directory_menu' && version_compare(Yii::$app->version, '1.9.0', 'ge')) {
                    $settings->set('instruction_location', 'top_menu');
                }
            } catch (\Throwable $e) {
                Yii::error($e);
            }
        }
    }

    public static function onIntegrityCheck($event) {
        try {
            $integrityController = $event->sender;

            $integrityController->showTestHeadline('HumHub DAV Access Module - Token (' . UserToken::find()->count() . ' entries)');
            foreach (UserToken::find()->joinWith(['user'])->each() as $userToken) {
                if ($userToken->user === null) {
                    if ($integrityController->showFix('Deleting token ' . $userToken->id . ' without existing user!')) {
                        $userToken->delete();
                    }
                    continue;
                }

                if ($userToken->user->status !== User::STATUS_ENABLED) {
                    if ($integrityController->showFix('Deleting token ' . $userToken->id . ' with disabled user!')) {
                        $userToken->delete();
                        continue;
                    }
                }

            }
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    public static function onUserDelete($event) {
        try {
            foreach (UserToken::findAll(['user_id' => $event->sender->id]) as $userToken) {
                $userToken->delete();
            }
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    public static function onUserUpdate($event) {
        if ($event->sender->status !== User::STATUS_ENABLED) {
            self::onUserDelete($event);
        }
    }
}
