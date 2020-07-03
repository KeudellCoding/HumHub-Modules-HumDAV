<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

use humhub\widgets\BaseMenu;
use humhub\components\Application;

return [
    'id' => 'humdav',
    'class' => 'humhub\modules\humdav\Module',
    'namespace' => 'humhub\modules\humdav',
    'events' => [
        [Application::class, Application::EVENT_BEFORE_REQUEST, ['\humhub\modules\humdav\Events', 'onBeforeRequest']],
        [\humhub\modules\directory\widgets\Menu::class, BaseMenu::EVENT_INIT, ['\humhub\modules\humdav\Events', 'onDirectoryMenuInit']]
    ]
];
