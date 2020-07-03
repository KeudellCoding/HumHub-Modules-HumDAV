<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\definitions;

use Yii;
use humhub\modules\user\models\User;
use humhub\libs\ProfileImage;

class VCardDefinitions {
    public static function getVCard(User $user) {
        $settings = Yii::$app->getModule('humdav')->settings;

        $profile = $user->profile;

        $vCard = "
        BEGIN:VCARD\r\n
        VERSION:4.0\r\n
        KIND:individual\r\n
        UID:$user->guid\r\n
        FN:$user->displayname\r\n
        N:$profile->lastname;$profile->firstname;;;\r\n
        EMAIL:$user->email\r\n
        ";

        if ((boolean)$settings->get('include_address', true) === true) {  // Adding Address
            $vCard .= "
            ADR;TYPE=home:;;$profile->street;$profile->city;$profile->state;$profile->zip;$profile->country\r\n
            ";
        }

        if ((boolean)$settings->get('include_profile_image', true) === true) {  // Add Profile Image
            $profileImage = new ProfileImage($user->guid);
            if ($profileImage->hasImage()) {
                $profileImageUrl = $profileImage->getUrl('', true);
                $vCard .= "
                PHOTO:$profileImageUrl\r\n
                ";
            }
        }
        
        if ((boolean)$settings->get('include_birthday', true) === true) {  // Add Birthday
            if (!empty($profile->birthday)) {
                $birthdayDatetime = date_create_from_format('Y-m-d', $profile->birthday);
                $birthdayFormated = '';
                if ($profile->birthday_hide_year === 1) {
                    $birthdayFormated = $birthdayDatetime->format('--md');
                }
                else {
                    $birthdayFormated = $birthdayDatetime->format('Ymd');
                }
                $vCard .= "
                BDAY:$birthdayFormated\r\n
                ";
            }
        }
        
        if ((boolean)$settings->get('include_gender', true) === true) {  // Add Gender
            if ($profile->gender === 'male') {
                $vCard .= "
                GENDER:M\r\n
                ";
            }
            else if ($profile->gender === 'female') {
                $vCard .= "
                GENDER:F\r\n
                ";
            }
            else {
                $vCard .= "
                GENDER:O\r\n
                ";
            }
        }

        if ((boolean)$settings->get('include_phone_numbers', true) === true) {  // Add Phone numbers
            if (!empty(str_replace(' ', '', $profile->phone_private))) {
                $phone = str_replace(' ', '', $profile->phone_private);
                $vCard .= "
                TEL;type=HOME;type=VOICE:$phone\r\n
                ";
            }
            if (!empty(str_replace(' ', '', $profile->phone_work))) {
                $phone = str_replace(' ', '', $profile->phone_work);
                $vCard .= "
                TEL;type=WORK;type=VOICE:$phone\r\n
                ";
            }
            if (!empty(str_replace(' ', '', $profile->mobile))) {
                $phone = str_replace(' ', '', $profile->mobile);
                $vCard .= "
                TEL;type=CELL;type=VOICE:$phone\r\n
                ";
            }
            if (!empty(str_replace(' ', '', $profile->fax))) {
                $phone = str_replace(' ', '', $profile->fax);
                $vCard .= "
                TEL;type=HOME;TYPE=FAX:$phone\r\n
                ";
            }
        }
        
        if ((boolean)$settings->get('include_url', true) === true) {  // Add Url's
            if (!empty($profile->url)) {
                $vCard .= "
                URL:$profile->url\r\n
                ";
            }
        }

        $vCard .= "
        END:VCARD\r\n
        ";
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
