<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\models\sabre;

class PrincipalCollection extends \Sabre\CalDAV\Principal\Collection {
    /**
     * @inheritdoc
     */
    function getChildForPrincipal(array $principal) {
        return new Principal($this->principalBackend, $principal);
    }
}