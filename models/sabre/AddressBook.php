<?php

/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\models\sabre;

class AddressBook extends \Sabre\CardDAV\AddressBook
{
    /**
     * @inheritdoc
     */
    public function getACL()
    {
        return [
            [
                'privilege' => '{DAV:}read',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getChildACL()
    {
        return [
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],
        ];
    }

    public function getChild($name)
    {
        $obj = $this->carddavBackend->getCard($this->addressBookInfo['id'], $name);
        if (!$obj) {
            throw new \Sabre\DAV\Exception\NotFound('Card not found');
        }
        return new Card($this->carddavBackend, $this->addressBookInfo, $obj);
    }

    public function getChildren()
    {
        $objs = $this->carddavBackend->getCards($this->addressBookInfo['id']);
        $children = [];
        foreach ($objs as $obj) {
            $obj['acl'] = $this->getChildACL();
            $children[] = new Card($this->carddavBackend, $this->addressBookInfo, $obj);
        }
        return $children;
    }

    public function getMultipleChildren(array $paths)
    {
        $objs = $this->carddavBackend->getMultipleCards($this->addressBookInfo['id'], $paths);
        $children = [];
        foreach ($objs as $obj) {
            $obj['acl'] = $this->getChildACL();
            $children[] = new Card($this->carddavBackend, $this->addressBookInfo, $obj);
        }
        return $children;
    }
}
