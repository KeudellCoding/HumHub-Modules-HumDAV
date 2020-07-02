<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

require_once __DIR__.'/../../vendor/autoload.php';

use Sabre\DAV\Server;
use humhub\modules\humdav\components\sabre\AuthenticationBackend;
use humhub\modules\humdav\components\sabre\CardDavBackend;
use humhub\modules\humdav\components\sabre\PrincipalBackend;

$settings = Yii::$app->getModule('humdav')->settings;

//Backends
$authBackend = new AuthenticationBackend();
$principalBackend = new PrincipalBackend();
$cardDavBackend = new CardDavBackend();

// Setting up the directory tree
$nodes = [
    new Sabre\DAVACL\PrincipalCollection($principalBackend),
    new Sabre\CardDAV\AddressBookRoot($principalBackend, $cardDavBackend),
];

// The object tree needs in turn to be passed to the server class
$server = new Server($nodes);
$server->setBaseUri('/humdav/remote');

// Plugins
$server->addPlugin(new Sabre\DAV\Auth\Plugin($authBackend));
if ((boolean)$settings->get('enable_browser_plugin', false) === true) {
    $server->addPlugin(new Sabre\DAV\Browser\Plugin());
}
$server->addPlugin(new Sabre\CardDAV\Plugin());
$server->addPlugin(new Sabre\DAVACL\Plugin());

// And off we go!
$server->exec();
