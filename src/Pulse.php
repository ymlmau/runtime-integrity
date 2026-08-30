<?php
namespace YmlMau\RuntimeIntegrity;

use YmlMau\RuntimeIntegrity\Integrity\Baseline;
use YmlMau\RuntimeIntegrity\Integrity\IntegrityChecker;
use YmlMau\RuntimeIntegrity\Reporting\ReportEvent;
use YmlMau\RuntimeIntegrity\Support\CanonicalJson;
use YmlMau\RuntimeIntegrity\Transport\TransportManager;

final class Pulse
{
    private $store;
    private $projectRoot;
    private $baselinePath;

    public function __construct(StateStore $store, $projectRoot, $baselinePath)
    {
        $this->store = $store;
        $this->projectRoot = $projectRoot;
        $this->baselinePath = $baselinePath;
    }

    public function isDue(array $document)
    {
        $nextDue = isset($document['state']['next_due']) ? (int) $document['state']['next_due'] : 0;
        return $nextDue <= time();
    }

    public function run()
    {
        $lock = $this->store->tryExclusiveLock();
        if ($lock === null) {
            return;
        }

        try {
            $document = $this->store->read();
            if (!is_array($document) || !$this->isDue($document)) {
                return;
            }
            $this->process($document);
        } catch (\Throwable $e) {
            // Fail-open by architecture: never propagate package failures into Yii.
        } finally {
            $this->store->releaseLock($lock);
        }
    }

    private function process(array $document)
    {
        $now = time();
        $document['state']['last_attempt'] = $now;
        $config = isset($document['config']) && is_array($document['config']) ? $document['config'] : Config::defaults();

        $configFingerprint = 'sha256:' . hash('sha256', CanonicalJson::encode($config));
        $document['state']['config_fingerprint'] = $configFingerprint;

        if ($this->retryPending($document, $config)) {
            $this->store->write($document);
            return;
        }

        $status = 'check_error';
        $expectedHash = null;
        $observedHash = null;
        $changes = ['modified' => [], 'deleted' => [], 'added' => []];
        $buildId = null;

        try {
            $baseline = Baseline::load($this->baselinePath);
            $trustedAlgorithm = isset($config['developer_auth']['algorithm']) ? $config['developer_auth']['algorithm'] : null;
            $trustedPublicKey = isset($config['developer_auth']['public_key']) ? $config['developer_auth']['public_key'] : null;
            if (!Baseline::verify($baseline, $trustedAlgorithm, $trustedPublicKey)) {
                $status = 'baseline_invalid';
                $document['baseline']['signature_status'] = 'invalid';
            } else {
                $payload = $baseline['payload'];
                $baselineProductId = isset($payload['product_id']) ? $payload['product_id'] : null;
                $configuredProductId = isset($config['product_id']) ? $config['product_id'] : null;
                if (!$baselineProductId || !$configuredProductId || !hash_equals((string) $configuredProductId, (string) $baselineProductId)) {
                    $status = 'baseline_invalid';
                    $document['baseline']['signature_status'] = 'valid_product_mismatch';
                } else {
                $buildId = isset($payload['build_id']) ? $payload['build_id'] : null;
                $expectedHash = isset($payload['root_hash']) ? $payload['root_hash'] : null;
                $expectedFiles = isset($payload['files']) && is_array($payload['files']) ? $payload['files'] : [];
                $checker = new IntegrityChecker();
                $include = isset($config['manifest']['include']) && is_array($config['manifest']['include']) ? $config['manifest']['include'] : [];
                $exclude = isset($config['manifest']['exclude']) && is_array($config['manifest']['exclude']) ? $config['manifest']['exclude'] : [];
                $observedFiles = $checker->scan($this->projectRoot, $include, $exclude);
                $observedHash = $checker->rootHash($observedFiles);
                if ($expectedHash !== null && hash_equals($expectedHash, $observedHash)) {
                    $status = 'clean';
                } else {
                    $comparison = $checker->compare($expectedFiles, $observedFiles);
                    $status = 'modified';
                    $changes = [
                        'modified' => $comparison['modified'],
                        'deleted' => $comparison['deleted'],
                        'added' => $comparison['added'],
                    ];
                }
                $document['baseline'] = [
                    'build_id' => $buildId,
                    'expected_hash' => $expectedHash,
                    'signature_status' => 'valid',
                ];
                }
            }
        } catch (\RuntimeException $e) {
            $status = strpos($e->getMessage(), 'missing') !== false ? 'baseline_missing' : 'check_error';
        } catch (\Throwable $e) {
            $status = 'check_error';
        }

        $previousIntegrity = isset($document['state']['last_integrity']) ? $document['state']['last_integrity'] : null;
        $incidentFingerprint = null;
        if ($status === 'modified') {
            $incidentFingerprint = 'sha256:' . hash('sha256', CanonicalJson::encode([
                'build_id' => $buildId,
                'expected_hash' => $expectedHash,
                'observed_hash' => $observedHash,
                'changes' => $changes,
            ]));
        }

        $eventType = 'heartbeat';
        if (empty($document['state']['first_seen_sent'])) {
            $eventType = 'first_seen';
        } elseif ($status === 'modified' && $incidentFingerprint !== (isset($document['state']['last_incident_fingerprint']) ? $document['state']['last_incident_fingerprint'] : null)) {
            $eventType = 'incident';
        } elseif ($status === 'clean' && $previousIntegrity === 'modified') {
            $eventType = 'recovered';
        } elseif ($status === 'baseline_invalid' || $status === 'baseline_missing') {
            $eventType = 'baseline_error';
        } elseif ($status === 'check_error') {
            $eventType = 'check_error';
        }

        $environmentFingerprint = EnvironmentFingerprint::create($this->projectRoot);
        $document['state']['environment_fingerprint'] = $environmentFingerprint;

        $event = [
            'schema' => 1,
            'event_type' => $eventType,
            'product' => [
                'product_id' => isset($config['product_id']) ? $config['product_id'] : null,
                'build_id' => $buildId,
                'monitor_version' => Config::MONITOR_VERSION,
            ],
            'installation' => [
                'installation_id' => $document['identity']['installation_id'],
                'environment_fingerprint' => $environmentFingerprint,
            ],
            'integrity' => [
                'status' => $status,
                'expected_hash' => $expectedHash,
                'observed_hash' => $observedHash,
            ],
        ];

        if (!empty($config['privacy']['include_hostname'])) {
            $event['installation']['hostname'] = php_uname('n');
        }


        if ($eventType === 'incident') {
            $event['incident'] = $this->limitedIncident($changes, $incidentFingerprint);
        } elseif ($status === 'modified') {
            $event['incident'] = [
                'fingerprint' => $incidentFingerprint,
                'persistent' => true,
            ];
        }

        if ($eventType === 'recovered' && !empty($document['state']['last_incident_fingerprint'])) {
            $event['previous_incident_fingerprint'] = $document['state']['last_incident_fingerprint'];
        }

        $report = ReportEvent::create($event);
        $enabledTransportCount = count((new TransportManager())->buildFromConfig($config));
        $failed = $this->deliver($report, $config, $document);
        if ($eventType === 'first_seen' && $enabledTransportCount > count($failed)) {
            $document['state']['first_seen_sent'] = true;
        }

        $document['state']['last_integrity'] = $status;
        $document['state']['last_observed_hash'] = $observedHash;
        if ($status === 'modified') {
            $document['state']['last_incident_fingerprint'] = $incidentFingerprint;
        } elseif ($status === 'clean') {
            $document['state']['last_incident_fingerprint'] = null;
        }

        if ($failed) {
            $document['state']['pending_event'] = [
                'event' => $report,
                'transports' => $failed,
            ];
            $document['state']['next_due'] = $this->retryDue();
        } else {
            unset($document['state']['pending_event']);
            if ($enabledTransportCount > 0) {
                $document['state']['last_success'] = $now;
            }
            $document['state']['next_due'] = $this->normalDue();
        }


        $this->store->write($document);
    }

    private function retryPending(array &$document, array $config)
    {
        if (empty($document['state']['pending_event']['event']) || empty($document['state']['pending_event']['transports'])) {
            return false;
        }
        $report = $document['state']['pending_event']['event'];
        $wanted = $document['state']['pending_event']['transports'];
        $failed = $this->deliver($report, $config, $document, $wanted);
        if ($failed) {
            $document['state']['pending_event']['transports'] = $failed;
            $document['state']['next_due'] = $this->retryDue();
        } else {
            unset($document['state']['pending_event']);
            $document['state']['last_success'] = time();
            $document['state']['next_due'] = $this->normalDue();
        }
        return true;
    }

    private function deliver(array $report, array $config, array &$document, array $onlyNames = null)
    {
        $manager = new TransportManager();
        $transports = $manager->buildFromConfig($config);
        $failed = [];
        foreach ($transports as $transport) {
            if ($onlyNames !== null && !in_array($transport->getName(), $onlyNames, true)) {
                continue;
            }
            try {
                $transport->send($report);
                $document['state']['delivery'][$transport->getName()]['last_success'] = time();
                $document['state']['delivery'][$transport->getName()]['retry_due'] = null;
            } catch (\Throwable $e) {
                $failed[] = $transport->getName();
                $document['state']['delivery'][$transport->getName()]['retry_due'] = $this->retryDue();
            }
        }
        return $failed;
    }

    private function limitedIncident(array $changes, $fingerprint)
    {
        $limit = 100;
        $out = ['fingerprint' => $fingerprint, 'truncated' => false];
        foreach (['modified', 'deleted', 'added'] as $type) {
            $list = isset($changes[$type]) ? $changes[$type] : [];
            $out[$type . '_count'] = count($list);
            $out[$type] = array_slice($list, 0, $limit);
            if (count($list) > $limit) {
                $out['truncated'] = true;
            }
        }
        return $out;
    }

    private function normalDue()
    {
        return time() + random_int(561600, 648000);
    }

    private function retryDue()
    {
        return time() + random_int(43200, 86400);
    }

}
