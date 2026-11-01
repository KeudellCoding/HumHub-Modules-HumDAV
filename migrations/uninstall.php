<?php

/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

use humhub\components\Migration;

/**
 * Class uninstall
 */
class uninstall extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->safeDropTable('humdav_user_token');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "uninstall does not support migration down.\n";
        return false;
    }
}
