<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

use humhub\modules\humdav\components\UUIDHelper;

$secureRequests = Yii::$app->request->getIsSecureConnection() ? 'true': 'false';

$mobileconfig = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
	<key>PayloadContent</key>
	<array>
		<dict>
			<key>CardDAVAccountDescription</key>
			<string>'.Yii::$app->settings->get('name').' CardDAV</string>
			<key>CardDAVHostName</key>
			<string>'.Yii::$app->request->getHostName().'</string>
			<key>CardDAVPort</key>
			<integer>'.Yii::$app->request->getServerPort().'</integer>
			<key>CardDAVPrincipalURL</key>
			<string>/humdav/remote/addressbooks/'.$currentIdentity->username.'/</string>
			<key>CardDAVUseSSL</key>
            <'.$secureRequests.'/>
            <key>CardDAVUsername</key>
            <string>'.$currentIdentity->username.'</string>
			<key>PayloadDescription</key>
			<string>CardDAV Configuration</string>
			<key>PayloadDisplayName</key>
			<string>'.$currentIdentity->username.' CardDAV</string>
			<key>PayloadIdentifier</key>
			<string>com.humdav.setup.carddav</string>
			<key>PayloadOrganization</key>
			<string></string>
			<key>PayloadType</key>
			<string>com.apple.carddav.account</string>
			<key>PayloadUUID</key>
			<string>'.UUIDHelper::generateNew().'</string>
			<key>PayloadVersion</key>
			<integer>1</integer>
		</dict>
	</array>
	<key>PayloadDescription</key>
	<string>Configuration profiles for the resources of the HumHub page '.Yii::$app->settings->get('name').'.</string>
	<key>PayloadDisplayName</key>
	<string>'.Yii::$app->settings->get('name').' HumDAV Configuration</string>
	<key>PayloadIdentifier</key>
	<string>com.humdav.setup</string>
	<key>PayloadOrganization</key>
	<string></string>
	<key>PayloadRemovalDisallowed</key>
	<false/>
	<key>PayloadType</key>
	<string>Configuration</string>
	<key>PayloadUUID</key>
	<string>'.UUIDHelper::generateNew().'</string>
	<key>PayloadVersion</key>
	<integer>1</integer>
</dict>
</plist>';

header('Content-Type: application/x-apple-aspen-config');
header('Content-Disposition: attachment; filename='.$currentIdentity->username.'-profile.mobileconfig'); 
header('Cache-Control: no-store');
header('Content-Length: '.strlen($mobileconfig));

echo $mobileconfig;
die();