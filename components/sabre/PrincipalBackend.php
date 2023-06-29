<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\components\sabre;

use Sabre\DAV\PropPatch;
use Sabre\DAVACL\PrincipalBackend\AbstractBackend;
use humhub\modules\user\models\User;

class PrincipalBackend extends AbstractBackend {
    private static function getUserPrincipal(User $user) {
        return [
            'id' => strval($user->id),
			'uri' => 'principals/' . $user->guid,
            '{DAV:}displayname' => $user->displayName,
            '{http://sabredav.org/ns}email-address' => $user->email
        ];
    }
    private static function getUserChildPrincipals(User $user) {
        return [
            'calendar-proxy-read' => [
                'id' => strval($user->id) . '-calendar-proxy-read',
                'uri' => 'principals/' . $user->guid . '/calendar-proxy-read'
            ],
            'calendar-proxy-write'  => [
                'id' => $user->id . 'calendar-proxy-write',
                'uri' => 'principals/' . $user->guid . '/calendar-proxy-write'
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function getPrincipalsByPrefix($prefixPath) {
        $principals = [];

        foreach (User::findAll(['user.status' => User::STATUS_ENABLED]) as $user) {
            $principals[] = self::getUserPrincipal($user);
            array_push($principals, ...array_values(self::getUserChildPrincipals($user)));
        }

        $results = array_filter($principals, function($principal) use ($prefixPath) {
            if (!isset($principal['uri'])) return false;
            return str_starts_with($principal['uri'], $prefixPath);
        });

        return $results;
    }

    /**
     * @inheritdoc
     */
    public function getPrincipalByPath($path) {
        $prefixParts = explode('/', $path, 3);
        if (count($prefixParts) < 2) {
            return null;
        }

        if ($prefixParts[0] === 'principals') {
            $user = User::findByGuid($prefixParts[1]);
            if ($user !== null) {

                if (isset($prefixParts[2])) {
                    $userChildPrincipals = self::getUserChildPrincipals($user);
                    if (isset($userChildPrincipals[$prefixParts[2]])) {
                        return $userChildPrincipals[$prefixParts[2]];
                    }
                }
                
				return self::getUserPrincipal($user);
            }
        }

        return null;
    }
    
    /**
     * @inheritdoc
     */
    public function updatePrincipal($path, PropPatch $propPatch) {
        $response = [
            403 => []
        ];
        
        foreach($propPatch as $key=>$value) {
            $response[403][$key] = null;
        }
        
        return $response;
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
        return false;
    }
}