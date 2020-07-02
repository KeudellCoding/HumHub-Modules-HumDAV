<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\models\admin;

use Yii;
use yii\base\Model;

class EditForm extends Model {
    public $active;
    public $include_address;
    public $include_profile_image;
    public $include_birthday;
    public $include_gender;
    public $include_phone_numbers;
    public $include_url;

    /**
     * @inheritdocs
     */
    public function rules() {
        return [
            [['active'], 'safe'],
            [['include_address', 'include_profile_image', 'include_birthday', 'include_gender', 'include_phone_numbers', 'include_url'], 'boolean'],
        ];
    }

    /**
     * @inheritdocs
     */
    public function init() {
        $settings = Yii::$app->getModule('humdav')->settings;
        $this->active = $settings->get('active', false);

        $this->active = $settings->get('active', false);

        $this->include_address = $settings->get('include_address', true);
        $this->include_profile_image = $settings->get('include_profile_image', true);
        $this->include_birthday = $settings->get('include_birthday', true);
        $this->include_gender = $settings->get('include_gender', true);
        $this->include_phone_numbers = $settings->get('include_phone_numbers', true);
        $this->include_url = $settings->get('include_url', true);
    }

    /**
     * Declares customized attribute labels.
     * If not declared here, an attribute would have a label that is
     * the same as its name with the first letter in upper case.
     */
    public function attributeLabels() {
        return [
            'active' => 'Enable Access'
        ];
    }

    /**
     * Saves the given form settings.
     */
    public function save() {
        $settings = Yii::$app->getModule('humdav')->settings;
        $settings->set('active', (boolean) $this->active);

        $settings->set('include_address', (boolean) $this->include_address);
        $settings->set('include_profile_image', (boolean) $this->include_profile_image);
        $settings->set('include_birthday', (boolean) $this->include_birthday);
        $settings->set('include_gender', (boolean) $this->include_gender);
        $settings->set('include_phone_numbers', (boolean) $this->include_phone_numbers);
        $settings->set('include_url', (boolean) $this->include_url);

        return true;
    }
}
