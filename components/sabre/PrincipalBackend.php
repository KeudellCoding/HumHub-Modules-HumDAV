<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\components\sabre;

use Sabre\Uri;
use Sabre\DAV\PropPatch;
use Sabre\DAVACL\PrincipalBackend\AbstractBackend;
use humhub\modules\humdav\definitions\PrincipalDefinitions;
use humhub\modules\user\models\User;

class PrincipalBackend extends AbstractBackend {
    /**
     * @inheritdoc
     */
    public function getPrincipalsByPrefix($prefixPath) {
        $results = [];

        if ($prefixPath === 'principals') {
            foreach (User::findAll(['user.status' => User::STATUS_ENABLED]) as $user) {
                $results[] = PrincipalDefinitions::getUserPrincipal($user);
            }
        }

        return $results;
    }

    /**
     * @inheritdoc
     */
    public function getPrincipalByPath($path) {
        list($prefix, $name) = Uri\split($path);

        if ($prefix === 'principals') {
            $user = User::findOne(['username' => $name]);
            if ($user !== null) {
				return PrincipalDefinitions::getUserPrincipal($user);
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function updatePrincipal($path, PropPatch $propPatch) {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof') {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getGroupMemberSet($principal) {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getGroupMembership($principal) {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function setGroupMemberSet($principal, array $members) {
        return;
    }
}