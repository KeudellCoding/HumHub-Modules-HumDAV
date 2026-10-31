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
            ['pattern' => '.well-known/carddav', 'route' => 'humdav/remote/discovery'],
            ['pattern' => 'humdav/remote', 'route' => 'humdav/remote'],
            ['pattern' => 'humdav/remote/<tmpParam:.*>', 'route' => 'humdav/remote'],

            ['pattern' => 'humdav/accessinfo/index', 'route' => 'humdav/accessinfo', 'verb' => ['GET', 'POST']],
            ['pattern' => 'humdav/accessinfo/token-info', 'route' => 'humdav/accessinfo/token-info', 'verb' => ['GET', 'POST']],
            ['pattern' => 'humdav/accessinfo/revoke-token', 'route' => 'humdav/accessinfo/revoke-token', 'verb' => ['GET', 'POST']],
            ['pattern' => 'humdav/accessinfo/generate-token', 'route' => 'humdav/accessinfo/generate-token', 'verb' => ['GET', 'POST']],
            ['pattern' => 'humdav/accessinfo/mobileconfig', 'route' => 'humdav/accessinfo/mobileconfig', 'verb' => ['GET']],

            // Config
            ['pattern' => 'humdav/admin/index', 'route' => 'humdav/admin', 'verb' => ['POST', 'GET']],

            // Catch all to ensure verbs
            ['pattern' => 'humdav/<tmpParam:.*>', 'route' => 'humdav/error/notfound']
        ];
    }
}
