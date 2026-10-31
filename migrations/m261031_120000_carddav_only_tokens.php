<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

use humhub\components\Migration;

/**
 * Retain existing CardDAV credentials and remove obsolete token purposes.
 */
class m261031_120000_carddav_only_tokens extends Migration {
    public function safeUp() {
        $table = $this->db->getTableSchema('humdav_user_token', true);
        if ($table === null) {
            throw new RuntimeException('The HumDAV token table is missing.');
        }

        if (isset($table->columns['used_for'])) {
            // Purpose 1 was DAV; iCal, disabled and unknown token types are revoked.
            $this->delete('humdav_user_token', ['or', ['<>', 'used_for', 1], ['used_for' => null]]);
            $this->dropColumn('humdav_user_token', 'used_for');
        }
    }

    public function safeDown() {
        echo "Removed legacy tokens cannot be restored.\n";
        return false;
    }
}
