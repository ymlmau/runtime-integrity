<?php
namespace YmlMau\RuntimeIntegrity;

final class AutoSetup
{
    private $store;

    public function __construct(StateStore $store)
    {
        $this->store = $store;
    }

    public function initialize(array $seedConfig)
    {
        $existing = $this->store->read();
        if (is_array($existing)) {
            $migrated = $this->migrate($existing);
            $migrated = (new IdentityManager())->ensure($migrated);
            if ($migrated !== $existing) {
                $this->store->write($migrated);
            }
            return $migrated;
        }

        $config = Config::merge(Config::defaults(), $seedConfig);
        $state = [
            'schema' => Config::SCHEMA_VERSION,
            'identity' => [],
            'config' => $config,
            'baseline' => [
                'build_id' => null,
                'expected_hash' => null,
                'signature_status' => null,
            ],
            'state' => [
                'last_attempt' => null,
                'last_success' => null,
                'next_due' => null,
                'last_integrity' => null,
                'last_observed_hash' => null,
                'last_incident_fingerprint' => null,
                'environment_fingerprint' => null,
                'config_fingerprint' => null,
                'delivery' => [
                    'email' => ['last_success' => null, 'retry_due' => null],
                    'api' => ['last_success' => null, 'retry_due' => null],
                ],
            ],
        ];
        $state = (new IdentityManager())->ensure($state);
        return $this->store->initialize($state);
    }

    private function migrate(array $document)
    {
        $schema = isset($document['schema']) ? (int) $document['schema'] : 1;

        if ($schema < 2) {
            if (isset($document['identity']['auth'])) {
                unset($document['identity']['auth']);
            }
            if (isset($document['state']['pending_event'])) {
                unset($document['state']['pending_event']);
            }
            $document['schema'] = 2;
            $schema = 2;
        }

        if ($schema !== Config::SCHEMA_VERSION) {
            throw new \RuntimeException('Unsupported runtime integrity state schema.');
        }

        return $document;
    }
}
