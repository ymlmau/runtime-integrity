<?php
spl_autoload_register(function ($class) {
    $prefix = 'YmlMau\\RuntimeIntegrity\\';
    if (strpos($class, $prefix) !== 0) return;
    $relative = substr($class, strlen($prefix));
    $path = dirname(__DIR__) . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) require $path;
});

use YmlMau\RuntimeIntegrity\AutoSetup;
use YmlMau\RuntimeIntegrity\Config;
use YmlMau\RuntimeIntegrity\Crypto\CryptoFactory;
use YmlMau\RuntimeIntegrity\Integrity\Baseline;
use YmlMau\RuntimeIntegrity\Integrity\IntegrityChecker;
use YmlMau\RuntimeIntegrity\Pulse;
use YmlMau\RuntimeIntegrity\StateStore;
use YmlMau\RuntimeIntegrity\Support\CanonicalJson;

function ok($condition, $message) {
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    echo "OK: {$message}\n";
}

function writeBaseline($root, $product, $build, $privateKey, $algorithm) {
    $checker = new IntegrityChecker();
    $files = $checker->scan($root, Config::defaults()['manifest']['include'], Config::defaults()['manifest']['exclude']);
    $payload = Baseline::buildPayload($product, $build, $files);
    $provider = CryptoFactory::forAlgorithm($algorithm);
    $signature = $provider->sign(CanonicalJson::encode($payload), $privateKey);
    $baseline = ['payload' => $payload, 'signature' => ['algorithm' => $algorithm, 'value' => $signature]];
    file_put_contents($root . '/.runtime-integrity.baseline', gzencode(CanonicalJson::encode($baseline) . "\n", 9));
}

function rrmdir($dir) {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) rrmdir($path); else unlink($path);
    }
    rmdir($dir);
}

$root = sys_get_temp_dir() . '/ri-pulse-' . bin2hex(random_bytes(4));
mkdir($root, 0700, true);
mkdir($root . '/models', 0700, true);
file_put_contents($root . '/models/Test.php', "<?php\nclass Test {}\n");

$provider = CryptoFactory::preferred();
$keys = $provider->generateKeyPair();
$algorithm = $provider->getAlgorithm();
$store = new StateStore($root . '/.runtime-integrity');
$seed = [
    'product_id' => 'pulse-qa',
    'developer_auth' => ['algorithm' => $algorithm, 'public_key' => $keys['public_key']],
    'email' => ['enabled' => false, 'relay_url' => null],
    'api' => ['enabled' => false, 'url' => null],
];
(new AutoSetup($store))->initialize($seed);
writeBaseline($root, 'pulse-qa', 'build-1', $keys['private_key'], $algorithm);

$document = $store->read();
$document['state']['next_due'] = 0;
$store->write($document);
$before = time();
(new Pulse($store, $root, $root . '/.runtime-integrity.baseline'))->run();
$after = $store->read();
ok($after['state']['last_integrity'] === 'clean', 'disabled transports still run integrity check');
ok($after['state']['next_due'] >= $before + 561600 && $after['state']['next_due'] <= time() + 648000, 'normal pulse schedules 6.5-7.5 day jitter');
ok(empty($after['state']['pending_event']), 'disabled transports create no pending event');
ok($after['state']['last_success'] === null, 'disabled transports do not fake delivery success');

$document = $store->read();
$document['state']['next_due'] = 0;
$store->write($document);
$lock = $store->tryExclusiveLock();
$lastAttempt = $document['state']['last_attempt'];
(new Pulse($store, $root, $root . '/.runtime-integrity.baseline'))->run();
$locked = $store->read();
$store->releaseLock($lock);
ok($locked['state']['last_attempt'] === $lastAttempt, 'flock contention skips duplicate pulse work');

$document = $store->read();
$document['config']['api'] = ['enabled' => true, 'url' => 'http://127.0.0.1:1/'];
$document['state']['next_due'] = 0;
$store->write($document);
$beforeFailure = time();
(new Pulse($store, $root, $root . '/.runtime-integrity.baseline'))->run();
$failed = $store->read();
ok(isset($failed['state']['pending_event']['transports']) && $failed['state']['pending_event']['transports'] === ['api'], 'failed API creates pending retry');
ok($failed['state']['next_due'] >= $beforeFailure + 43200 && $failed['state']['next_due'] <= time() + 86400, 'failed transport schedules 12-24 hour retry');
ok($failed['state']['last_integrity'] === 'clean', 'transport failure remains fail-open for integrity state');

$document = $store->read();
unset($document['state']['pending_event']);
$document['config']['api'] = ['enabled' => false, 'url' => null];
$document['state']['next_due'] = 0;
$store->write($document);
file_put_contents($root . '/models/Test.php', "<?php\nclass Test { public function changed() {} }\n");
(new Pulse($store, $root, $root . '/.runtime-integrity.baseline'))->run();
$modified = $store->read();
ok($modified['state']['last_integrity'] === 'modified' && !empty($modified['state']['last_incident_fingerprint']), 'modified pulse records incident fingerprint');

file_put_contents($root . '/models/Test.php', "<?php\nclass Test {}\n");
$document = $store->read();
$document['state']['next_due'] = 0;
$store->write($document);
(new Pulse($store, $root, $root . '/.runtime-integrity.baseline'))->run();
$recovered = $store->read();
ok($recovered['state']['last_integrity'] === 'clean' && $recovered['state']['last_incident_fingerprint'] === null, 'restored files return pulse state to CLEAN');

rrmdir($root);
echo "PULSE PASS\n";
