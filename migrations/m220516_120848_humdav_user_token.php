<?php

/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

use humhub\components\Migration;

/**
 * Class m220516_120848_humdav_user_token
 */
class m220516_120848_humdav_user_token extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->safeCreateTable('humdav_user_token', [
            'id' => $this->primaryKey()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'name' => $this->string()->notNull(),
            'algorithm' => $this->string(20)->notNull(),
            'token' => $this->text()->notNull(),
            'salt' => $this->text()->notNull(),
            'used_for' => $this->tinyInteger(4)->notNull(),
            'last_time_used' => $this->dateTime(),
            'last_time_used_by_ip' => $this->string(),
            'last_time_used_by_user_agent' => $this->string(),
            'created_at' => $this->dateTime()->notNull(),
            'created_by_ip' => $this->string()->notNull(),
            'created_by_user_agent' => $this->string()->notNull(),
        ]);

        $this->safeAddForeignKey('fk_humdav_user_token_user_id', 'humdav_user_token', 'user_id', 'user', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->safeDropTable('humdav_user_token');
    }
}
