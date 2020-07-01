<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\components\sabre;

use Sabre\DAV\PropPatch;
use Sabre\CardDAV\Backend\AbstractBackend;
use humhub\modules\humdav\definitions\PrincipalDefinitions;
use humhub\modules\humdav\definitions\VCardDefinitions;
use humhub\modules\user\models\User;

class CardDavBackend extends AbstractBackend {
    public function getAllAddressBooks() {
        return [
            new \humhub\modules\humdav\models\AvailableAddressBooks\MainAddressBook(),
            new \humhub\modules\humdav\models\AvailableAddressBooks\FollowingAddressBook()
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAddressBooksForUser($principalUri) {
        $results = [];

        foreach ($this->getAllAddressBooks() as $addressBook) {
            $results[] = PrincipalDefinitions::getAddressBookPrincipal($addressBook, $principalUri);
        }
        
        return $results;
    }

    /**
     * @inheritdoc
     */
    public function updateAddressBook($addressBookId, PropPatch $propPatch) {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function createAddressBook($principalUri, $url, array $properties) {
        throw new DAV\Exception\BadRequest('Not implemented yet!');
    }

    /**
     * @inheritdoc
     */
    public function deleteAddressBook($addressBookId) {
        return;
    }

    /**
     * @inheritdoc
     */
    public function getCards($addressBookId) {
        $addressBook = null;
        foreach($this->getAllAddressBooks() as $addressBookToTest) {
            if ($addressBookToTest->Id === $addressBookId) {
                $addressBook = $addressBookToTest;
                break;
            }
        }

        if ($addressBook !== null) {
            $results = [];

            foreach ($addressBook->getUsers() as $user) {
                $results[] = VCardDefinitions::getVCardDefinition($user, $addressBook->Id);
            }

            return $results;
        }
        
        return [];
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
        return null;
    }

    /**
     * @inheritdoc
     */
    public function updateCard($addressBookId, $cardUri, $cardData) {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function deleteCard($addressBookId, $cardUri) {
        return false;
    }
}