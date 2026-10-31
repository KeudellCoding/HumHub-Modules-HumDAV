<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav;

use yii\helpers\Url;

class Module extends \humhub\components\Module {
    /**
     * @inheritdoc
     */
    public $resourcesPath = 'resources';
    
    /**
     * @inheritdoc
     */
    public function getConfigUrl() {
        return Url::to(['/humdav/admin/index']);
    }
}
