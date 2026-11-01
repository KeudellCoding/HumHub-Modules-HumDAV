<?php

/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\models\sabre;

class PrincipalCollection extends \Sabre\DAVACL\PrincipalCollection
{
    /**
     * @inheritdoc
     */
    public function getChildForPrincipal(array $principal)
    {
        return new Principal($this->principalBackend, $principal);
    }
}
