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

class CardDavBackend extends AbstractBackend {
    /**
     * @inheritdoc
     */
    public function getAddressBooksForUser($principalUri) {
        list($prefix, $guid) = Uri\split($principalUri);
        if ($prefix !== 'principals') return [];

        $user = User::findByGuid($guid);
        if ($user === null) return [];

        $globalSettings = Yii::$app->getModule('humdav')->settings;
        $userSettings = $globalSettings->contentContainer($user);
        $enableSpaceAddressbooks = $globalSettings->get('enable_space_addressbooks', true);
        $groupsAsAddressbooks = $userSettings->get('provide_groups_as_separate_addressbooks', false);

        $results = [
            [
                'id' => '0',
                'uri' => 'allusers',
                'principaluri' => $principalUri,
                '{DAV:}displayname' => 'All Users',
                '{'.Plugin::NS_CARDDAV.'}addressbook-description' => 'All Users from "'.Yii::$app->settings->get('name').'"'
            ]
        ];

        if ($groupsAsAddressbooks) {
            $results[] = [
                'id' => '1',
                'uri' => 'following',
                'principaluri' => $principalUri,
                '{DAV:}displayname' => 'Following',
                '{'.Plugin::NS_CARDDAV.'}addressbook-description' => 'All users you are following'
            ];
        }
        
        if ($enableSpaceAddressbooks && $groupsAsAddressbooks) {
            foreach (Membership::getUserSpaces($user->id) as $space) {
                $results[] = [
                    'id' => 'space_'.$space->id,
                    'uri' => 'space_'.$space->url,
                    'principaluri' => $principalUri,
                    '{DAV:}displayname' => $space->getDisplayName(),
                    '{'.Plugin::NS_CARDDAV.'}addressbook-description' => $space->getDisplayNameSub()
                ];
            }
        }
        
        return $results;
    }

    /**
     * @inheritdoc
     */
    public function updateAddressBook($addressBookId, PropPatch $propPatch) {
        throw new \Sabre\DAV\Exception\BadRequest('Not implemented yet!');
    }

    /**
     * @inheritdoc
     */
    public function createAddressBook($principalUri, $url, array $properties) {
        throw new \Sabre\DAV\Exception\BadRequest('Not implemented yet!');
    }

    /**
     * @inheritdoc
     */
    public function deleteAddressBook($addressBookId) {
        throw new \Sabre\DAV\Exception\BadRequest('Not implemented yet!');
    }

    /**
     * @inheritdoc
     */
    public function getCards($addressBookId) {
        $results = [];
        $usersForCards = [];

        $currentUser = Yii::$app->user->getIdentity();
        if ($currentUser === null) {
            return [];
        }

        $settings = Yii::$app->getModule('humdav')->settings;

        if ($addressBookId === '0') {
            $usersForCards = User::find()->active()->visible()->all();
        }
        else if ($addressBookId === '1') {
            $usersForCards = Follow::getFollowedUserQuery($currentUser)->active()->visible()->all();
        }
        else if (str_starts_with($addressBookId, 'space_') && $settings->get('enable_space_addressbooks', true)) {
            $usersForCards = $this->getSpaceUsers(substr($addressBookId, strpos($addressBookId, '_') + 1), $currentUser);
        }

        foreach ($usersForCards as $user) {
            $vCard = VCardDefinitions::getVCardDefinition($user, $addressBookId, $currentUser);
            if ($vCard !== null) {
                $results[] = $vCard;
            }
        }

        return $results;
    }

    /**
     * @inheritdoc
     */
    public function getCard($addressBookId, $cardUri) {
        $cards = $this->getCards($addressBookId);
        foreach ($cards as $card) {
            if ($card['uri'] === $cardUri) {
                return $card;
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function createCard($addressBookId, $cardUri, $cardData) {
        throw new \Sabre\DAV\Exception\BadRequest('Not implemented yet!');
    }

    /**
     * @inheritdoc
     */
    public function updateCard($addressBookId, $cardUri, $cardData) {
        throw new \Sabre\DAV\Exception\BadRequest('Not implemented yet!');
    }

    /**
     * @inheritdoc
     */
    public function deleteCard($addressBookId, $cardUri) {
        throw new \Sabre\DAV\Exception\BadRequest('Not implemented yet!');
    }

    // ==================================================================================================================

    private function getSpaceUsers(string $spaceId, User $currentUser) {
        if (!in_array($spaceId, Membership::getUserSpaceIds($currentUser->id))) {
            return [];
        }
        
        $space = Space::findOne(['id' => $spaceId]);
        if ($space === null) {
            return [];
        }

        return Membership::getSpaceMembersQuery($space)->active()->visible()->all();
    }
}
