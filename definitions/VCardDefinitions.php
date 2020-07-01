<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\definitions;

use humhub\modules\user\models\User;

class VCardDefinitions {
    public static function getVCard(User $user) {
        $profile = $user->profile;
        $vCard = <<<VCF
        BEGIN:VCARD
        VERSION:4.0
        UID:$user->guid
        FN:$profile->firstname $profile->lastname
        N:$profile->lastname;$profile->firstname;;;
        EMAIL:$user->email
        ADR;TYPE=home:;;$profile->street;$profile->city;$profile->state;$profile->zip;$profile->country 
        END:VCARD
        VCF;

        return $vCard;
    }

    public static function getVCardDefinition(User $user, int $addressBookId) {
        $vCard = self::getVCard($user);
        return [
            'id' => $user->id,
            'uri' => $user->username,
            'addressbookid' => $addressBookId,
            'lastmodified' => date_timestamp_get(date_create_from_format('Y-m-d H:i:s', $user->updated_at)),
            'carddata' => $vCard,
            'size' => strlen($vCard),
            'etag' => '"'.md5($vCard).'"'
        ];
    }
}
