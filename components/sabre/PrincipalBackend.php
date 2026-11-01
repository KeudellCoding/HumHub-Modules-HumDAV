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
use humhub\modules\humdav\definitions\VCardDefinitions;
use humhub\modules\user\models\User;
use Yii;

class PrincipalBackend extends AbstractBackend
{
    private static function getUserPrincipal(User $user)
    {
        $principal = [
            'id' => $user->id,
            'uri' => 'principals/' . $user->guid,
            '{DAV:}displayname' => $user->displayName,
        ];

        $currentUser = Yii::$app->user->getIdentity();
        $email = $currentUser !== null ? VCardDefinitions::getEmailAddress($user, $currentUser) : null;
        if ($email !== null) {
            $principal['{http://sabredav.org/ns}email-address'] = $email;
        }

        return $principal;
    }

    /**
     * @inheritdoc
     */
    public function getPrincipalsByPrefix($prefixPath)
    {
        $results = [];

        $user = Yii::$app->user->getIdentity();
        if ($prefixPath === 'principals' && $user !== null) {
            $results[] = self::getUserPrincipal($user);
        }

        return $results;
    }

    /**
     * @inheritdoc
     */
    public function getPrincipalByPath($path)
    {
        list($prefix, $guid) = Uri\split($path);

        $user = Yii::$app->user->getIdentity();
        if ($prefix === 'principals') {
            if ($user !== null && $user->guid === $guid) {
                return self::getUserPrincipal($user);
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function updatePrincipal($path, PropPatch $propPatch)
    {
        $response = [
            403 => [],
        ];

        foreach ($propPatch as $key => $value) {
            $response[403][$key] = null;
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof')
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getGroupMemberSet($principal)
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getGroupMembership($principal)
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function setGroupMemberSet($principal, array $members)
    {
        return false;
    }
}
