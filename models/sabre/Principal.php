<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\models\sabre;

class Principal extends \Sabre\CalDAV\Principal\User {
    /**
     * @inheritdoc
     */
    function getACL() {
        $acls = parent::getACL();

        foreach ($acls as $aclKey => $acl) {
            if ($acl['principal'] === '{DAV:}owner') {
                $acls[$aclKey]['privilege'] = '{DAV:}read';
            }
        }

        return $acls;
    }
}