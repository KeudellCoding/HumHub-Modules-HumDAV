<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\models;

use Yii;
use yii\base\Model;

class UserSettingsEditForm extends Model {
    public $provide_groups_as_separate_addressbooks;

    /**
     * @inheritdocs
     */
    public function rules() {
        return [
            [['provide_groups_as_separate_addressbooks'], 'boolean']
        ];
    }

    /**
     * @inheritdocs
     */
    public function init() {
        parent::init();
        $settings = Yii::$app->getModule('humdav')->settings->user();
        $this->provide_groups_as_separate_addressbooks = $settings->get('provide_groups_as_separate_addressbooks', false);
    }

    /**
     * @inheritdoc
     */
    public function attributeHints() {
        return [
            'provide_groups_as_separate_addressbooks' => 'This setting has no effect on macOS, it only works on iOS. Contacts may be displayed multiple times due to a bug in the Contacts app on iOS. Android and other systems can read the group memberships directly from the contact (CATEGORIES property).'
        ];
    }

    /**
     * Saves the given form settings.
     */
    public function save() {
        $settings = Yii::$app->getModule('humdav')->settings->user();
        $settings->set('provide_groups_as_separate_addressbooks', (bool)$this->provide_groups_as_separate_addressbooks);

        return true;
    }
}
