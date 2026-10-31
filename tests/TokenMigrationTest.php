<?php

use humhub\modules\humdav\models\UserToken;
use humhub\modules\user\models\User;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/migrations/m261031_120000_carddav_only_tokens.php';

final class TokenMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        // A connection-local copy shadows the real table without modifying its rows.
        Yii::$app->db->createCommand('CREATE TEMPORARY TABLE humdav_token_template LIKE humdav_user_token')->execute();
        Yii::$app->db->createCommand('CREATE TEMPORARY TABLE humdav_user_token LIKE humdav_token_template')->execute();
        Yii::$app->db->createCommand('DROP TEMPORARY TABLE humdav_token_template')->execute();
        Yii::$app->db->schema->refreshTableSchema(UserToken::tableName());
    }

    protected function tearDown(): void
    {
        Yii::$app->db->createCommand('DROP TEMPORARY TABLE IF EXISTS humdav_user_token')->execute();
        Yii::$app->db->createCommand('DROP TEMPORARY TABLE IF EXISTS humdav_token_template')->execute();
        Yii::$app->db->schema->refreshTableSchema(UserToken::tableName());
    }

    private function migration(): m261031_120000_carddav_only_tokens
    {
        return new m261031_120000_carddav_only_tokens([
            'db' => Yii::$app->db,
            'compact' => true,
        ]);
    }

    private function legacyToken(string $name, string $algorithm, ?int $purpose = null): array
    {
        $secret = 'legacy-secret-' . $name;
        $salt = 'legacy-salt-' . $name;
        $value = $secret . $salt;
        $hash = match ($algorithm) {
            'sha1md5' => sha1(md5($value)),
            'sha512' => hash('sha512', $value),
            'sha512whirlpool' => hash('sha512', hash('whirlpool', $value)),
        };
        $row = [
            'user_id' => User::find()->active()->one()->id,
            'name' => $name,
            'algorithm' => $algorithm,
            'token' => $hash,
            'salt' => $salt,
            'created_at' => '2023-06-29 12:00:00',
            'created_by_ip' => '127.0.0.1',
            'created_by_user_agent' => 'Legacy client',
        ];
        if (Yii::$app->db->getTableSchema(UserToken::tableName(), true)->getColumn('used_for') !== null) {
            $row['used_for'] = $purpose;
        }
        Yii::$app->db->createCommand()->insert(UserToken::tableName(), $row)->execute();
        return [(int)Yii::$app->db->getLastInsertID(), $secret];
    }

    private function rows(): array
    {
        return Yii::$app->db->createCommand('SELECT * FROM humdav_user_token ORDER BY id')->queryAll();
    }

    public function testLegacyPurposesArePurgedAndDavCredentialsKeepWorking(): void
    {
        Yii::$app->db->createCommand()->addColumn(UserToken::tableName(), 'used_for', 'tinyint NULL')->execute();
        $credentials = [];
        $algorithms = ['sha1md5', 'sha512'];
        if (in_array('whirlpool', hash_algos(), true)) {
            $algorithms[] = 'sha512whirlpool';
        }
        foreach ($algorithms as $algorithm) {
            $credentials[] = $this->legacyToken('CardDAV-' . $algorithm, $algorithm, 1);
        }
        $expected = $this->rows();
        foreach ($expected as &$row) {
            unset($row['used_for']);
        }
        unset($row);
        $revoked = [];
        foreach ([0, 2, 7, null] as $purpose) {
            $revoked[] = $this->legacyToken('Obsolete-' . ($purpose ?? 'null'), 'sha512', $purpose);
        }

        self::assertNotFalse($this->migration()->up());
        self::assertNull(Yii::$app->db->getTableSchema(UserToken::tableName(), true)->getColumn('used_for'));
        self::assertSame($expected, $this->rows());

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $settings = Yii::$app->getModule('humdav')->settings;
            $settings->set('active', true);
            $settings->setSerialized('enabled_users', []);
            $user = User::find()->active()->one();
            foreach ($credentials as [$id, $secret]) {
                self::assertTrue(UserToken::validateTokenForUser($user, $secret));
            }
            foreach ($revoked as [$id, $secret]) {
                self::assertFalse(UserToken::validateTokenForUser($user, $secret));
            }
        } finally {
            $transaction->rollBack();
        }
    }

    public function testAlreadyMigratedTableIsUnchanged(): void
    {
        $this->legacyToken('Current CardDAV', 'sha512');
        $expected = $this->rows();
        self::assertNotFalse($this->migration()->up());
        self::assertNotFalse($this->migration()->up());
        self::assertSame($expected, $this->rows());
    }
}
