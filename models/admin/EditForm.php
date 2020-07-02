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
    public $enabled_users;
    public $include_address;
    public $include_profile_image;
    public $include_birthday;
    public $include_gender;
    public $include_phone_numbers;
    public $include_url;
    public $enable_browser_plugin;

    /**
     * @inheritdocs
     */
    public function rules() {
        return [
            [['active', 'enabled_users'], 'safe'],
            [['include_address', 'include_profile_image', 'include_birthday', 'include_gender', 'include_phone_numbers', 'include_url', 'enable_browser_plugin'], 'boolean'],
        ];
    }

    /**
     * @inheritdocs
     */
    public function init() {
        $settings = Yii::$app->getModule('humdav')->settings;
        $this->active = $settings->get('active', false);

        $this->enabled_users = (array)$settings->getSerialized('enabled_users');

        $this->include_address = $settings->get('include_address', true);
        $this->include_profile_image = $settings->get('include_profile_image', true);
        $this->include_birthday = $settings->get('include_birthday', true);
        $this->include_gender = $settings->get('include_gender', true);
        $this->include_phone_numbers = $settings->get('include_phone_numbers', true);
        $this->include_url = $settings->get('include_url', true);

        $this->enable_browser_plugin = $settings->get('enable_browser_plugin', false);
    }

    /**
     * @inheritdocs
     */
    public function attributeLabels() {
        return [
            'active' => 'Enable Module',
            'enable_browser_plugin' => 'Enable Browser Access (not recommended)'
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints() {
        return [
            'enabled_users' => 'If empty, all users have access.'
        ];
    }

    /**
     * Saves the given form settings.
     */
    public function save() {
        $settings = Yii::$app->getModule('humdav')->settings;
        $settings->set('active', (boolean) $this->active);

        $settings->setSerialized('enabled_users', (array)$this->enabled_users);

        $settings->set('include_address', (boolean) $this->include_address);
        $settings->set('include_profile_image', (boolean) $this->include_profile_image);
        $settings->set('include_birthday', (boolean) $this->include_birthday);
        $settings->set('include_gender', (boolean) $this->include_gender);
        $settings->set('include_phone_numbers', (boolean) $this->include_phone_numbers);
        $settings->set('include_url', (boolean) $this->include_url);

        $settings->set('enable_browser_plugin', (boolean) $this->enable_browser_plugin);

        return true;
    }
}
