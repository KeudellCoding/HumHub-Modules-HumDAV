<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\models;

abstract class AddressBook {
    public int $Id;
    public string $Uri;
    public string $DisplayName;
    public string $Description;
    public int $SyncToken = 1;

    abstract public function getUsers();
}