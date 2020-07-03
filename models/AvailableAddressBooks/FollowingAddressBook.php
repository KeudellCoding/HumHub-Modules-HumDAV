<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\models\AvailableAddressBooks;

use Yii;
use humhub\modules\humdav\models;
use humhub\modules\user\models\User;
use humhub\modules\user\models\Follow;
use humhub\modules\humdav\models\AddressBook;

class FollowingAddressBook extends AddressBook {
    public $Id = 1;
    public $Uri = 'following';
    public $DisplayName = 'Following';
    public $Description = 'All users you are following';
    public $SyncToken = 1;

    public function getUsers() {
        list($username) = Yii::$app->request->getAuthCredentials();
        $currentUser = User::findOne(['username' => $username]);
        if ($currentUser === null) {
            return [];
        }

        $followers = Follow::findAll(['user_id' => $currentUser->id, 'object_model' => User::class]);
        
        $users = [];
        foreach ($followers as $follower) {
            $users[] = User::findOne(['id' => $follower->object_id]);
        }
        return $users;
    }
}