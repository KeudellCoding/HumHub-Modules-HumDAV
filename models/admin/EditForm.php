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

    /**
     * @inheritdocs
     */
    public function rules() {
        return [
            [['active'], 'safe']
        ];
    }

    /**
     * @inheritdocs
     */
    public function init() {
        $settings = Yii::$app->getModule('humdav')->settings;
        $this->active = $settings->get('active', false);
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

        return true;
    }
}
