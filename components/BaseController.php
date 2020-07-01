<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\components;

use humhub\components\Controller;

abstract class BaseController extends Controller {
    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false;
}
