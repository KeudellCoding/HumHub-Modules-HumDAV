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
    public $Id = 0;
    public $Uri = 'main';
    public $DisplayName = 'All Users';
    public $Description = 'All Users';
    public $SyncToken = 1;

    public function getUsers() {
        return User::findAll(['user.status' => User::STATUS_ENABLED]);
    }
}