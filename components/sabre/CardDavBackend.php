<?php

/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\components\sabre;

use Yii;
use Sabre\Uri;
use Sabre\DAV\PropPatch;
use Sabre\CardDAV\Backend\AbstractBackend;
use Sabre\CardDAV\Plugin;
use humhub\modules\humdav\definitions\VCardDefinitions;
use humhub\modules\user\models\User;
use humhub\modules\user\models\Follow;
use humhub\modules\space\models\Space;
use humhub\modules\space\models\Membership;

class CardDavBackend extends AbstractBackend
{
    /**
     * @inheritdoc
     */
    public function getAddressBooksForUser($principalUri)
    {
        list($prefix, $guid) = Uri\split($principalUri);
        if ($prefix !== 'principals') {
            return [];
        }

        $user = Yii::$app->user->getIdentity();
        if ($user === null || $user->guid !== $guid) {
            return [];
        }

        $globalSettings = Yii::$app->getModule('humdav')->settings;
        $userSettings = $globalSettings->contentContainer($user);
        $enableSpaceAddressbooks = $globalSettings->get('enable_space_addressbooks', true);
        $groupsAsAddressbooks = $userSettings->get('provide_groups_as_separate_addressbooks', false);

        $results = [
            [
                'id' => '0',
                'uri' => 'allusers',
                'principaluri' => $principalUri,
                '{DAV:}displayname' => Yii::t('HumdavModule.base', 'All Users'),
                '{' . Plugin::NS_CARDDAV . '}addressbook-description' => Yii::t('HumdavModule.base', 'All users from "{network}"', ['network' => Yii::$app->settings->get('name')]),
            ],
        ];

        if ($groupsAsAddressbooks) {
            $results[] = [
                'id' => '1',
                'uri' => 'following',
                'principaluri' => $principalUri,
                '{DAV:}displayname' => Yii::t('HumdavModule.base', 'Following'),
                '{' . Plugin::NS_CARDDAV . '}addressbook-description' => Yii::t('HumdavModule.base', 'All users you are following'),
            ];
        }

        if ($enableSpaceAddressbooks && $groupsAsAddressbooks) {
            foreach (Membership::getUserSpaces($user->id) as $space) {
                $results[] = [
                    'id' => 'space_' . $space->id,
                    'uri' => 'space_' . $space->url,
                    'principaluri' => $principalUri,
                    '{DAV:}displayname' => $space->getDisplayName(),
                    '{' . Plugin::NS_CARDDAV . '}addressbook-description' => $space->getDisplayNameSub(),
                ];
            }
        }

        return $results;
    }

    /**
     * @inheritdoc
     */
    public function updateAddressBook($addressBookId, PropPatch $propPatch)
    {
        throw new \Sabre\DAV\Exception\BadRequest('Not implemented yet!');
    }

    /**
     * @inheritdoc
     */
    public function createAddressBook($principalUri, $url, array $properties)
    {
        throw new \Sabre\DAV\Exception\BadRequest('Not implemented yet!');
    }

    /**
     * @inheritdoc
     */
    public function deleteAddressBook($addressBookId)
    {
        throw new \Sabre\DAV\Exception\BadRequest('Not implemented yet!');
    }

    /**
     * @inheritdoc
     */
    public function getCards($addressBookId)
    {
        $query = $this->getUsersQuery($addressBookId);
        return $query === null ? [] : $this->buildCards($query->all(), $addressBookId);
    }

    /**
     * @inheritdoc
     */
    public function getCard($addressBookId, $cardUri)
    {
        return $this->getMultipleCards($addressBookId, [$cardUri])[0] ?? false;
    }

    public function getMultipleCards($addressBookId, array $uris)
    {
        $guids = [];
        foreach ($uris as $uri) {
            if (preg_match('/^[a-f0-9-]{36}\.vcf$/iD', $uri)) {
                $guids[] = substr($uri, 0, -4);
            }
        }
        $query = $this->getUsersQuery($addressBookId);
        if ($query === null || $guids === []) {
            return [];
        }
        return $this->buildCards($query->andWhere(['user.guid' => array_unique($guids)])->all(), $addressBookId);
    }

    /**
     * @inheritdoc
     */
    public function createCard($addressBookId, $cardUri, $cardData)
    {
        throw new \Sabre\DAV\Exception\BadRequest('Not implemented yet!');
    }

    /**
     * @inheritdoc
     */
    public function updateCard($addressBookId, $cardUri, $cardData)
    {
        throw new \Sabre\DAV\Exception\BadRequest('Not implemented yet!');
    }

    /**
     * @inheritdoc
     */
    public function deleteCard($addressBookId, $cardUri)
    {
        throw new \Sabre\DAV\Exception\BadRequest('Not implemented yet!');
    }

    // ==================================================================================================================

    private function getUsersQuery($addressBookId)
    {
        $currentUser = Yii::$app->user->getIdentity();
        if ($currentUser === null) {
            return null;
        }

        if ((string)$addressBookId === '0') {
            return User::find()->active()->visible()->with('profile');
        }

        $settings = Yii::$app->getModule('humdav')->settings;
        $userSettings = $settings->contentContainer($currentUser);
        if (!$userSettings->get('provide_groups_as_separate_addressbooks', false)) {
            return null;
        }

        if ((string)$addressBookId === '1') {
            return Follow::getFollowedUserQuery($currentUser)->active()->visible()->with('profile');
        }

        if (str_starts_with((string)$addressBookId, 'space_') && $settings->get('enable_space_addressbooks', true)) {
            $spaceId = substr($addressBookId, 6);
            if (in_array($spaceId, Membership::getUserSpaceIds($currentUser->id))) {
                $space = Space::findOne(['id' => $spaceId]);
                if ($space !== null) {
                    return Membership::getSpaceMembersQuery($space)->active()->visible()->with('profile');
                }
            }
        }

        return null;
    }

    private function buildCards(array $users, $addressBookId): array
    {
        $cards = [];
        $currentUser = Yii::$app->user->getIdentity();
        foreach ($users as $user) {
            $card = VCardDefinitions::getVCardDefinition($user, $addressBookId, $currentUser);
            if ($card !== null) {
                $cards[] = $card;
            }
        }
        return $cards;
    }
}
