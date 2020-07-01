<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\models\AvailableAddressBooks;

use humhub\modules\humdav\models;
use humhub\modules\user\models\User;
use humhub\modules\humdav\models\AddressBook;

class MainAddressBook extends AddressBook {
    public int $Id = 0;
    public string $Uri = 'main';
    public string $DisplayName = 'All Users';
    public string $Description = 'All Users';
    public int $SyncToken = 1;

    public function getUsers() {
        return User::findAll(['user.status' => User::STATUS_ENABLED]);
    }
}