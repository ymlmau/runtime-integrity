<?php
namespace YmlMau\RuntimeIntegrity\Reporting;

use YmlMau\RuntimeIntegrity\Crypto\CryptoFactory;
use YmlMau\RuntimeIntegrity\Support\CanonicalJson;
use YmlMau\RuntimeIntegrity\Support\Uuid;

final class SignedReportEvent
{
    public static function create(array $event, array $auth)
    {
        if (empty($event['event_id'])) {
            $event['event_id'] = Uuid::v4();
        }
        if (empty($event['timestamp'])) {
            $event['timestamp'] = gmdate('c');
        }
        $provider = CryptoFactory::forAlgorithm($auth['algorithm']);
        $canonical = CanonicalJson::encode($event);
        return [
            'event' => $event,
            'auth' => [
                'algorithm' => $auth['algorithm'],
                'key_id' => $auth['key_id'],
                'public_key' => isset($auth['public_key']) ? $auth['public_key'] : null,
                'signature' => $provider->sign($canonical, $auth['private_key']),
            ],
        ];
    }
}
