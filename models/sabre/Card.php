<?php

/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\models\sabre;

class Card extends \Sabre\CardDAV\Card
{
    /**
     * @inheritdoc
     */
    public function getACL()
    {
        // An alternative acl may be specified through the cardData array.
        if (isset($this->cardData['acl'])) {
            return $this->cardData['acl'];
        }

        return [
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->addressBookInfo['principaluri'],
                'protected' => true,
            ],
        ];
    }
}
