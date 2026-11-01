<?php

/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\models\sabre;

use Sabre\DAV\Exception\NotFound;
use Sabre\DAVACL\ACLTrait;
use Sabre\DAVACL\IACL;
use Yii;

class AddressBookRoot extends \Sabre\CardDAV\AddressBookRoot implements IACL
{
    use ACLTrait;

    /**
     * @inheritdoc
     */
    public function getACL()
    {
        return [
            [
                'privilege' => '{DAV:}read',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getChildForPrincipal(array $principal)
    {
        $user = Yii::$app->user->getIdentity();
        if ($user === null || $principal['uri'] !== 'principals/' . $user->guid) {
            throw new NotFound('Address book not found');
        }
        return new AddressBookHome($this->carddavBackend, $principal['uri']);
    }
}
