<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\models\sabre;

class CalendarHome extends \Sabre\CalDAV\CalendarHome {
    /**
     * @inheritdoc
     */
    function getACL() {
        return [
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->principalInfo['uri'],
                'protected' => true
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->principalInfo['uri'].'/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->principalInfo['uri'].'/calendar-proxy-read',
                'protected' => true,
            ],
        ];
    }
}