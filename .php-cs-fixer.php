<?php

/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

$humhubRoot = getenv('HUMHUB_ROOT') ?: dirname(__DIR__, 3);
$directory = getcwd();
try {
    chdir($humhubRoot);
    $config = require '.php-cs-fixer.php';
} finally {
    chdir($directory);
}

return $config->setFinder(
    (new PhpCsFixer\Finder())
        ->in(__DIR__)
        ->exclude(['vendor', 'views', 'messages']),
);
