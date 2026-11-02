<?php

/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\definitions;

use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\fieldtype\UserEmail;
use humhub\modules\user\models\Follow;
use humhub\modules\user\models\User;
use humhub\modules\user\permissions\ViewAboutPage;
use Sabre\VObject\Component\VCard;
use Yii;

class VCardDefinitions
{
    public static function getVCard(User $user, User $currentUser)
    {
        $settings = Yii::$app->getModule('humdav')->settings;
        $profile = $user->profile;
        if ($profile === null) {
            return null;
        }

        // The display name is public profile identity. Personal data requires About access.
        $vCard = new VCard(['VERSION' => '4.0', 'UID' => $user->guid, 'KIND' => 'individual']);
        $vCard->add('FN', $user->displayName);

        $visibleFields = [];
        $canViewAbout = $user->getPermissionManager($currentUser)->can(ViewAboutPage::class);
        $profileFields = $canViewAbout ? $profile->getProfileFields() : [];
        foreach ($profileFields as $field) {
            $visibleFields[$field->internal_name] = true;
        }
        $value = static fn(string $name) => isset($visibleFields[$name]) && $profile->hasAttribute($name)
            ? $profile->getAttribute($name)
            : null;

        if ($value('lastname') !== null || $value('firstname') !== null) {
            $vCard->add('N', [
                $value('lastname'),   // Family Names (also known as surnames)
                $value('firstname'),  // Given Names
                null,                 // Additional Names
                null,                 // Honorific Prefixes
                null,                 // Honorific Suffixes
            ]);
        }
        $email = self::getEmailAddress($user, $currentUser, $profileFields);
        if ($email !== null) {
            $vCard->add('EMAIL', $email);
        }

        $categories = [Yii::t('HumdavModule.base', 'All Users')];
        if (Follow::findOne(['user_id' => $currentUser->id, 'object_model' => User::class, 'object_id' => $user->id]) !== null) {
            $categories[] = Yii::t('HumdavModule.base', 'Following');
        }
        $sharedSpaceIds = array_intersect(Membership::getUserSpaceIds($user->id), Membership::getUserSpaceIds($currentUser->id));
        foreach (Space::findAll(['id' => $sharedSpaceIds]) as $space) {
            $categories[] = $space->name;
        }
        $vCard->add('CATEGORIES', $categories);

        if ($settings->get('include_address', false)) {
            $address = [
                null,               // post office box
                null,               // extended address (e.g., apartment or suite number)
                $value('street'),   // street address
                $value('city'),     // locality (e.g., city)
                $value('state'),    // region (e.g., state or province)
                $value('zip'),      // postal code
                $value('country'),  // country name
            ];
            if (array_filter($address) !== []) {
                $vCard->add('ADR', $address, ['TYPE' => 'home']);
            }
        }
        if ($canViewAbout && $settings->get('include_profile_image', false)) {
            $profileImage = $user->getImage();
            if ($profileImage->exists()) {
                $vCard->add('PHOTO', $profileImage->getUrl([], true));
            }
        }
        if ($settings->get('include_gender', false) && $value('gender')) {
            $vCard->add('GENDER', match ($value('gender')) {
                'male' => 'M',
                'female' => 'F',
                default => 'O',
            });
        }
        if ($settings->get('include_birthday', false) && $value('birthday')) {
            $birthday = date_create_from_format('!Y-m-d', $value('birthday'));
            if ($birthday !== false) {
                $vCard->add('BDAY', $birthday->format($profile->birthday_hide_year ? '--md' : 'Ymd'));
            }
        }
        if ($settings->get('include_url', false) && $value('url')) {
            $vCard->add('URL', $value('url'));
        }
        if ($settings->get('include_phone_numbers', false)) {
            foreach ([
                'phone_private' => ['home', 'voice'],
                'phone_work' => ['work', 'voice'],
                'mobile' => ['cell', 'voice', 'text'],
                'fax' => ['home', 'fax'],
            ] as $field => $types) {
                $number = str_replace(' ', '', $value($field) ?? '');
                if ($number !== '') {
                    $vCard->add('TEL', $number, ['TYPE' => $types]);
                }
            }
        }

        $validationResults = $vCard->validate();
        if ($validationResults !== []) {
            Yii::warning($validationResults, __METHOD__);
            return null;
        }
        return $vCard->serialize();
    }

    /**
     * Apply the same e-mail export policy to vCards and principal metadata.
     *
     * @param \humhub\modules\user\models\ProfileField[]|null $visibleProfileFields
     */
    public static function getEmailAddress(User $user, User $currentUser, ?array $visibleProfileFields = null): ?string
    {
        if (!Yii::$app->getModule('humdav')->settings->get('include_email', false)
            || empty($user->email)
            || $user->profile === null
            || !$user->getPermissionManager($currentUser)->can(ViewAboutPage::class)) {
            return null;
        }

        # Check field visibility and return the e-mail address if a visible e-mail profile field is found.
        $visibleProfileFields ??= $user->profile->getProfileFields();
        foreach ($visibleProfileFields as $field) {
            if ($field->visible && $field->getFieldType() instanceof UserEmail) {
                return $user->email;
            }
        }

        return null;
    }

    public static function getVCardDefinition(User $user, $addressBookId, User $currentUser)
    {
        $vCard = self::getVCard($user, $currentUser);
        if ($vCard === null) {
            return null;
        }
        return [
            'id' => $user->id,
            'uri' => $user->guid . '.vcf',
            'addressbookid' => $addressBookId,
            'lastmodified' => strtotime($user->updated_at) ?: 0,
            'carddata' => $vCard,
            'size' => strlen($vCard),
            'etag' => '"' . md5($vCard) . '"',
        ];
    }
}
