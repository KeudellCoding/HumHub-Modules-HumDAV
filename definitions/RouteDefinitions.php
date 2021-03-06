<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\definitions;

class RouteDefinitions {
    public static function getDefinitions() {
        return [
            ['pattern' => 'humdav/remote', 'route' => 'humdav/remote'],
            ['pattern' => 'humdav/remote/<tmpParam:.*>', 'route' => 'humdav/remote'],

            ['pattern' => 'humdav/generate/mobileconfig', 'route' => 'humdav/generate/mobileconfig', 'verb' => ['GET']],

            ['pattern' => 'humdav/accessinfos', 'route' => 'humdav/directory/accessinfos', 'verb' => ['GET']],

            // Config
            ['pattern' => 'humdav/admin/index', 'route' => 'humdav/admin', 'verb' => ['POST', 'GET']],
            ['pattern' => 'humdav/admin/update', 'route' => 'humdav/admin/update', 'verb' => ['POST', 'GET']],

            // Catch all to ensure verbs
            ['pattern' => 'humdav/<tmpParam:.*>', 'route' => 'humdav/error/notfound']
        ];
    }
}