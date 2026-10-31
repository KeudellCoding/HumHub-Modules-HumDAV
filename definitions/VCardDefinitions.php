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
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\Follow;
use Sabre\VObject\Component\VCard;

class VCardDefinitions {
    public static function getVCard(User $user, User $currentUser) {
        $settings = Yii::$app->getModule('humdav')->settings;

        $profile = $user->profile;
        if ($profile === null) {
            return null;
        }

        $vCard = new VCard(['VERSION' => '4.0', 'UID' => $user->guid, 'KIND' => 'individual']);


        $vCard->add('FN', $user->displayName);
        $vCard->add('N', [
            $profile->lastname,     // Family Names (also known as surnames)
            $profile->firstname,    // Given Names
            null,                   // Additional Names
            null,                   // Honorific Prefixes
            null                    // Honorific Suffixes
        ]);
        $vCard->add('EMAIL', $user->email);


        $categories = ['All Users'];
        if (Follow::findOne(['user_id' => $currentUser->id, 'object_model' => User::class, 'object_id' => $user->id]) !== null) {
            $categories[] = 'Following';
        }
        foreach (array_intersect(Membership::getUserSpaceIds($user->id), Membership::getUserSpaceIds($currentUser->id)) as $spaceId) {
            $categories[] = Space::findOne(['id' => $spaceId])->name;
        }
        $vCard->add('CATEGORIES', $categories);


        if ((bool)$settings->get('include_address', true) === true) {  // Adding Address
            $vCard->add('ADR', [
                null,               // post office box
                null,               // extended address (e.g., apartment or suite number)
                $profile->street,   // street address
                $profile->city,     // locality (e.g., city)
                $profile->state,    // region (e.g., state or province)
                $profile->zip,      // postal code
                $profile->country   // country name
            ], ['TYPE' => 'home']);
        }


        if ((bool)$settings->get('include_profile_image', true) === true) {  // Add Profile Image
            $profileImage = $user->getImage();
            if ($profileImage->exists()) {
                $vCard->add('PHOTO', $profileImage->getUrl([], true));
            }
        }


        if ((bool)$settings->get('include_gender', true) === true) {  // Add Gender
            if (!empty($profile->gender)) {
                if ($profile->gender === 'male') $vCard->add('GENDER', 'M');
                else if ($profile->gender === 'female') $vCard->add('GENDER', 'F');
                else $vCard->add('GENDER', 'O');
            }
        }


        if ((bool)$settings->get('include_birthday', true) === true) {  // Add Birthday
            if (!empty($profile->birthday)) {
                $bdayDateTime = date_create_from_format('Y-m-d', $profile->birthday);
                if ($bdayDateTime !== false) {
                    $bdayFormated = $profile->birthday_hide_year ? $bdayDateTime->format('--md') : $bdayDateTime->format('Ymd');
                    $vCard->add('BDAY', $bdayFormated);
                }
            }
        }


        if ((bool)$settings->get('include_url', true) === true) {  // Add Url's
            if (!empty($profile->url)) {
                $vCard->add('URL', $profile->url);
            }
        }


        if ((bool)$settings->get('include_phone_numbers', true) === true) {  // Add Phone numbers
            if (!empty(str_replace(' ', '', $profile->phone_private ?? ''))) {
                $vCard->add('TEL', str_replace(' ', '', $profile->phone_private), ['TYPE' => ['home', 'voice']]);
            }
            if (!empty(str_replace(' ', '', $profile->phone_work ?? ''))) {
                $vCard->add('TEL', str_replace(' ', '', $profile->phone_work), ['TYPE' => ['work', 'voice']]);
            }
            if (!empty(str_replace(' ', '', $profile->mobile ?? ''))) {
                $vCard->add('TEL', str_replace(' ', '', $profile->mobile), ['TYPE' => ['cell', 'voice', 'text']]);
            }
            if (!empty(str_replace(' ', '', $profile->fax ?? ''))) {
                $vCard->add('TEL', str_replace(' ', '', $profile->fax), ['TYPE' => ['home', 'fax']]);
            }
        }


        $validationResults = $vCard->validate();
        if (!empty($validationResults)) {
            Yii::warning($validationResults, __METHOD__);
            return null;
        }
        return $vCard->serialize();
    }

    public static function getVCardDefinition(User $user, $addressBookId, User $currentUser) {
        $vCard = self::getVCard($user, $currentUser);
        if ($vCard === null) return null;
        return [
            'id' => $user->id,
            'uri' => $user->guid.'.vcf',
            'addressbookid' => $addressBookId,
            'lastmodified' => date_timestamp_get(date_create_from_format('Y-m-d H:i:s', $user->updated_at)),
            'carddata' => $vCard,
            'size' => strlen($vCard),
            'etag' => '"'.md5($vCard).'"'
        ];
    }
}
