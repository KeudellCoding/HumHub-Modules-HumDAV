<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

use humhub\commands\IntegrityController;
use humhub\components\Application;
use humhub\modules\humdav\Events;
use humhub\modules\marketplace\components\OnlineModuleManager;
use humhub\modules\user\models\User;
use humhub\widgets\TopMenu;

return [
    'id' => 'humdav',
    'class' => 'humhub\modules\humdav\Module',
    'namespace' => 'humhub\modules\humdav',
    'events' => [
        [Application::class, Application::EVENT_BEFORE_REQUEST, [Events::class, 'onBeforeRequest']],
        [TopMenu::class, TopMenu::EVENT_INIT, [Events::class, 'onTopMenuInit']],
        [OnlineModuleManager::class, OnlineModuleManager::EVENT_AFTER_UPDATE, [Events::class, 'onAfterUpdate']],
        [IntegrityController::class, IntegrityController::EVENT_ON_RUN, [Events::class, 'onIntegrityCheck']],
        [User::class, User::EVENT_BEFORE_DELETE, [Events::class, 'onUserDelete']],
        [User::class, User::EVENT_AFTER_UPDATE, [Events::class, 'onUserUpdate']]
    ]
];
