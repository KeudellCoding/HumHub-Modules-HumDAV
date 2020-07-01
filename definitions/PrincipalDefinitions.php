<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\definitions;

use humhub\modules\user\models\User;
use humhub\modules\humdav\models\AddressBook;

class PrincipalDefinitions {
    public static function getUserPrincipal(User $user) {
        return [
            'id' => $user->id,
			'uri' => 'principals/' . $user->username,
            '{DAV:}displayname' => $user->displayName,
            '{http://sabredav.org/ns}email-address' => $user->email
        ];
    }

    public static function getAddressBookPrincipal(AddressBook $addressBook, string $principalUri) {
        return [
            'id' => $addressBook->Id,
            'uri' => $addressBook->Uri,
            'principaluri' => $principalUri,
            'displayname' => $addressBook->DisplayName,
            'description' => $addressBook->Description,
            'synctoken' => $addressBook->SyncToken
        ];
    }
}
