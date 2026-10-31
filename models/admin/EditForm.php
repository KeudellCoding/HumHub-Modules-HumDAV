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
    public $instruction_location;
    public $instruction_location_sort_order;
    public $enabled_users;
    public $enable_password_auth;
    public $include_address;
    public $include_profile_image;
    public $include_birthday;
    public $include_gender;
    public $include_phone_numbers;
    public $include_url;
    public $enable_space_addressbooks;
    public $enable_browser_plugin;

    /**
     * @inheritdocs
     */
    public function rules() {
        return [
            [['instruction_location'], 'in', 'range' => array_keys(static::getWidgetLocations())],
            [['instruction_location_sort_order'], 'number', 'min' => 0],
            [['active', 'enabled_users'], 'safe'],
            [['enable_password_auth', 'include_address', 'include_profile_image', 'include_birthday', 'include_gender', 'include_phone_numbers', 'include_url', 'enable_space_addressbooks', 'enable_browser_plugin'], 'boolean'],
            [['instruction_location_sort_order'], 'required']
        ];
    }

    /**
     * @inheritdocs
     */
    public function init() {
        parent::init();
        $settings = Yii::$app->getModule('humdav')->settings;
        $this->active = $settings->get('active', false);

        $this->instruction_location = $settings->get('instruction_location');
        $this->instruction_location_sort_order = $settings->get('instruction_location_sort_order', 400);

        $this->enabled_users = (array)$settings->getSerialized('enabled_users');
        $this->enable_password_auth = $settings->get('enable_password_auth', false);

        $this->include_address = $settings->get('include_address', true);
        $this->include_profile_image = $settings->get('include_profile_image', true);
        $this->include_birthday = $settings->get('include_birthday', true);
        $this->include_gender = $settings->get('include_gender', true);
        $this->include_phone_numbers = $settings->get('include_phone_numbers', true);
        $this->include_url = $settings->get('include_url', true);

        $this->enable_space_addressbooks = $settings->get('enable_space_addressbooks', true);
        
        $this->enable_browser_plugin = $settings->get('enable_browser_plugin', false);
    }

    /**
     * @inheritdocs
     */
    public function attributeLabels() {
        return [
            'active' => 'Enable Module',
            'enable_password_auth' => 'Activate the password authentication (not recommended)',
            'enable_browser_plugin' => 'Enable Browser Access (not recommended)'
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints() {
        $result = [
            'enabled_users' => 'If empty, all users have access.',
            'enable_password_auth' => 'The token authentication works independently of the authentication with the user password.'
        ];

        return $result;
    }

    /**
     * Saves the given form settings.
     */
    public function save() {
        $settings = Yii::$app->getModule('humdav')->settings;
        $settings->set('active', (bool)$this->active);

        $settings->set('instruction_location', $this->instruction_location);
        $settings->set('instruction_location_sort_order', $this->instruction_location_sort_order);

        $settings->setSerialized('enabled_users', (array)$this->enabled_users);
        $settings->set('enable_password_auth', (bool)$this->enable_password_auth);

        $settings->set('include_address', (bool)$this->include_address);
        $settings->set('include_profile_image', (bool)$this->include_profile_image);
        $settings->set('include_birthday', (bool)$this->include_birthday);
        $settings->set('include_gender', (bool)$this->include_gender);
        $settings->set('include_phone_numbers', (bool)$this->include_phone_numbers);
        $settings->set('include_url', (bool)$this->include_url);

        $settings->set('enable_space_addressbooks', (bool)$this->enable_space_addressbooks);
        
        $settings->set('enable_browser_plugin', (bool)$this->enable_browser_plugin);

        return true;
    }

    public static function getWidgetLocations() {
        return [
            'dont_show' => 'Don\'t show (The module will work anyway)',
            'top_menu' => 'Top menu'
        ];
    }
}
