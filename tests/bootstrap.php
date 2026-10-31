<?php

/**
 * Run against a disposable copy of an installed HumHub database only.
 */
$dsn = getenv('HUMDAV_TEST_DB_DSN');
if (!$dsn || !preg_match('/dbname=humdav_test_[a-zA-Z0-9_]+(?:;|$)/', $dsn)) {
    throw new RuntimeException('HUMDAV_TEST_DB_DSN must point to a disposable humdav_test_* database.');
}

$protectedPath = getenv('HUMDAV_TEST_PROTECTED_PATH') ?: '/opt/humhub/protected';
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');
require $protectedPath . '/vendor/autoload.php';
require $protectedPath . '/vendor/yiisoft/yii2/Yii.php';
Yii::setAlias('@humhub', $protectedPath . '/humhub');
Yii::setAlias('@humhub/modules/humdav', dirname(__DIR__));

$_ENV['HUMHUB_CONFIG__COMPONENTS__DB__DSN'] = $dsn;
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'HumDAV integration tests';
$_SERVER['HTTP_HOST'] = 'localhost:8100';
$_SERVER['SERVER_PORT'] = '8100';
$_SERVER['REQUEST_METHOD'] = 'GET';

$bootstrap = new humhub\services\BootstrapService();
$bootstrap->setPaths(config: getenv('HUMDAV_TEST_CONFIG_PATH') ?: '/data/config');
$config = $bootstrap->getConfig('console');
$config['components']['cache'] = ['class' => yii\caching\ArrayCache::class];
$config['components']['request'] = [
    'class' => yii\web\Request::class,
    'scriptFile' => '/app/public/index.php',
    'scriptUrl' => '/index.php',
    'baseUrl' => '',
    'hostInfo' => 'http://localhost:8100',
    'enableCookieValidation' => false,
];
$config['components']['log']['targets'] = [];
$config['runtimePath'] = sys_get_temp_dir() . '/humdav-tests-' . getmypid();
yii\helpers\FileHelper::createDirectory($config['runtimePath']);
$app = new humhub\components\console\Application($config);
$app->user->setIdentity(humhub\modules\user\models\User::find()->active()->one());

$calendarPath = getenv('HUMDAV_TEST_CALENDAR_PATH');
if ($calendarPath) {
    $app->moduleManager->register($calendarPath);
    $calendar = $app->moduleManager->getModule('calendar');
    ob_start();
    try {
        if ($calendar->enable() === false) {
            throw new RuntimeException('Could not enable Calendar in the disposable database.');
        }
        ob_end_clean();
    } catch (Throwable $error) {
        fwrite(STDERR, ob_get_clean());
        throw $error;
    }
    require $calendarPath . '/vendor/autoload.php';
}

require dirname(__DIR__) . '/vendor/autoload.php';

ob_start();
try {
    if ($app->getModule('humdav')->getMigrationService()->migrateUp() === false) {
        throw new RuntimeException('Could not migrate HumDAV in the disposable database.');
    }
    ob_end_clean();
} catch (Throwable $error) {
    fwrite(STDERR, ob_get_clean());
    throw $error;
}
