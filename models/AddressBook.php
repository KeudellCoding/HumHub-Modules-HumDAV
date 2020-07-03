<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\models;

abstract class AddressBook {
    /**
     * @var int
     */
    public $Id;

    /**
     * @var string
     */
    public $Uri;

    /**
     * @var string
     */
    public $DisplayName;

    /**
     * @var string
     */
    public $Description;

    /**
     * @var int
     */
    public $SyncToken = 1;

    abstract public function getUsers();
}