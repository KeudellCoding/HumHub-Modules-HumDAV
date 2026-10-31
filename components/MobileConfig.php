<?php

namespace humhub\modules\humdav\components;

use humhub\modules\user\models\User;
use humhub\modules\calendar\helpers\AuthTokenService;
use InvalidArgumentException;
use XMLWriter;
use Yii;
use yii\helpers\Url;

/**
 * Builds Apple configuration profiles without emitting headers or storing tokens.
 */
final class MobileConfig {
    public static function build(User $user, string $token, string $target, bool $includeCalDav = true): string {
        if (!in_array($target, ['ios', 'osx'], true)) {
            throw new InvalidArgumentException('Unsupported configuration profile target.');
        }

        $name = Yii::$app->settings->get('name');
        $id = UUIDHelper::generateNewFromStrings($name, $user->username, 'HumDAV');
        $accounts = [
            'CardDAV' => [
                'principalUrl' => rtrim(Url::to(['/humdav/remote/principals/' . $user->guid], $target === 'ios'), '/') . '/',
                'password' => $token,
            ],
        ];
        $calendar = Yii::$app->getModule('calendar');
        if ($includeCalDav && $calendar !== null && $calendar->getIsEnabled()) {
            $accounts['CalDAV'] = [
                'principalUrl' => rtrim(Url::to(['/calendar/cal-dav/index'], $target === 'ios'), '/')
                    . '/principals/' . rawurlencode($user->username) . '/',
                'password' => AuthTokenService::instance()->calDavEncrypt($user),
            ];
        }

        $payloads = [];
        foreach ($accounts as $protocol => $account) {
            $payloadId = UUIDHelper::generateNewFromStrings($protocol, $id);
            $payloads[] = [
                $protocol . 'AccountDescription' => $name . ' ' . $protocol,
                $protocol . 'HostName' => Yii::$app->request->hostName,
                $protocol . 'Port' => Yii::$app->request->serverPort,
                $protocol . 'PrincipalURL' => $account['principalUrl'],
                $protocol . 'UseSSL' => Yii::$app->request->isSecureConnection,
                $protocol . 'Username' => $user->username,
                $protocol . 'Password' => $account['password'],
                'PayloadDescription' => $protocol . ' Configuration',
                'PayloadDisplayName' => $user->username . ' ' . $protocol,
                'PayloadIdentifier' => 'com.humdav.setup.' . strtolower($protocol) . '.' . $payloadId,
                'PayloadOrganization' => '',
                'PayloadType' => 'com.apple.' . strtolower($protocol) . '.account',
                'PayloadUUID' => $payloadId,
                'PayloadVersion' => 1,
            ];
        }

        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->writeDtd('plist', '-//Apple//DTD PLIST 1.0//EN', 'http://www.apple.com/DTDs/PropertyList-1.0.dtd');
        $writer->startElement('plist');
        $writer->writeAttribute('version', '1.0');
        self::writeValue($writer, [
            'PayloadContent' => $payloads,
            'PayloadDescription' => 'Configuration profiles for the resources of the HumHub page ' . $name . '.',
            'PayloadDisplayName' => $name . ' HumDAV Configuration',
            'PayloadIdentifier' => 'com.humdav.setup.' . $id,
            'PayloadOrganization' => '',
            'PayloadRemovalDisallowed' => false,
            'PayloadType' => 'Configuration',
            'PayloadUUID' => $id,
            'PayloadVersion' => 1,
        ]);
        $writer->endElement();
        $writer->endDocument();

        return $writer->outputMemory();
    }

    private static function writeValue(XMLWriter $writer, mixed $value): void {
        if (is_array($value)) {
            $isList = array_is_list($value);
            $writer->startElement($isList ? 'array' : 'dict');
            foreach ($value as $key => $item) {
                if (!$isList) {
                    $writer->writeElement('key', $key);
                }
                self::writeValue($writer, $item);
            }
            $writer->endElement();
        } elseif (is_bool($value)) {
            $writer->writeElement($value ? 'true' : 'false');
        } elseif (is_int($value)) {
            $writer->writeElement('integer', (string)$value);
        } else {
            $writer->writeElement('string', (string)$value);
        }
    }
}
