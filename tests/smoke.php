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
use YmlMau\RuntimeIntegrity\StateStore;
use YmlMau\RuntimeIntegrity\Crypto\CryptoFactory;
use YmlMau\RuntimeIntegrity\Integrity\Baseline;
use YmlMau\RuntimeIntegrity\Integrity\IntegrityChecker;
use YmlMau\RuntimeIntegrity\Reporting\ReportEvent;
use YmlMau\RuntimeIntegrity\Support\CanonicalJson;

function ok($condition, $message) {
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    echo "OK: {$message}\n";
}

$root = sys_get_temp_dir() . '/ri-smoke-' . bin2hex(random_bytes(4));
mkdir($root, 0700, true);
mkdir($root . '/models', 0700, true);
file_put_contents($root . '/models/Test.php', "<?php\nclass Test {}\n");

$store = new StateStore($root . '/.runtime-integrity');
$setup = new AutoSetup($store);
$state1 = $setup->initialize(['product_id' => 'smoke']);
$state2 = $setup->initialize(['product_id' => 'ignored-after-first-setup']);
ok($state1['schema'] === Config::SCHEMA_VERSION, 'state schema is current');
ok($state1['identity']['installation_id'] === $state2['identity']['installation_id'], 'installation identity persists');
ok(!isset($state1['identity']['auth']), 'installation identity contains no certificate or private key');
ok($state2['config']['product_id'] === 'smoke', 'first setup config remains canonical');

// Package-owned manifest policy refreshes across monitor updates without replacing runtime config.
$stale = $state2;
$stale['config']['manifest'] = [
    'include' => ['composer.json', 'web'],
    'exclude' => ['assets'],
];
$store->write($stale);
$refreshed = $setup->initialize(['product_id' => 'must-not-replace-runtime-product']);
ok($refreshed['config']['product_id'] === 'smoke', 'manifest refresh preserves runtime product config');
ok(in_array('assets', $refreshed['config']['manifest']['include'], true), 'manifest policy refresh adds current protected source assets');
ok(in_array('web/debug', $refreshed['config']['manifest']['exclude'], true), 'manifest policy refresh applies current generated debug exclusion');
$state2 = $refreshed;

$provider = CryptoFactory::preferred();
$keys = $provider->generateKeyPair();
$checker = new IntegrityChecker();
$files = $checker->scan($root, ['models'], ['.runtime-integrity']);
$payload = Baseline::buildPayload('smoke', 'build-1', $files);
$sig = $provider->sign(CanonicalJson::encode($payload), $keys['private_key']);
$baseline = ['payload' => $payload, 'signature' => ['algorithm' => $provider->getAlgorithm(), 'value' => $sig]];
ok(Baseline::verify($baseline, $provider->getAlgorithm(), $keys['public_key']), 'developer baseline signature verifies');

$observed = $checker->scan($root, ['models'], ['.runtime-integrity']);
$cmp = $checker->compare($payload['files'], $observed);
ok($cmp['clean'] === true, 'unchanged protected files are CLEAN');

file_put_contents($root . '/models/Test.php', "<?php\nclass Test { public function changed() {} }\n");
$observed2 = $checker->scan($root, ['models'], ['.runtime-integrity']);
$cmp2 = $checker->compare($payload['files'], $observed2);
ok($cmp2['clean'] === false && count($cmp2['modified']) === 1, 'modified protected file is detected');

// Generated Yii paths must be excluded while source AssetBundle code remains protected.
mkdir($root . '/assets', 0700, true);
file_put_contents($root . '/assets/AppAsset.php', "<?php\nclass AppAsset {}\n");
mkdir($root . '/web', 0700, true);
mkdir($root . '/web/assets', 0700, true);
file_put_contents($root . '/web/assets/generated.js', "generated\n");
mkdir($root . '/web/debug', 0700, true);
file_put_contents($root . '/web/debug/session.json', "generated debug\n");
mkdir($root . '/frontend', 0700, true);
mkdir($root . '/frontend/runtime', 0700, true);
file_put_contents($root . '/frontend/runtime/cache.tmp', "temporary\n");
$manifestFiles = $checker->scan($root, Config::defaults()['manifest']['include'], Config::defaults()['manifest']['exclude']);
ok(isset($manifestFiles['assets/AppAsset.php']), 'source assets directory remains protected');
ok(!isset($manifestFiles['web/assets/generated.js']), 'published web/assets are excluded');
ok(!isset($manifestFiles['web/debug/session.json']), 'generated web/debug files are excluded');
ok(!isset($manifestFiles['frontend/runtime/cache.tmp']), 'advanced runtime directory is excluded');

$report = ReportEvent::create(['schema' => 1, 'event_type' => 'heartbeat']);
ok(!empty($report['event_id']) && !empty($report['timestamp']), 'report gets event id and timestamp');
ok(!isset($report['auth']), 'report has no per-installation cryptographic envelope');

$state2['state']['last_integrity'] = 'clean';
$store->write($state2);
$readBack = $store->read();
ok($readBack['state']['last_integrity'] === 'clean', 'atomic state write/read succeeds');

// Simulate automatic migration from v1 state containing installation keys.
$v1 = $readBack;
$v1['schema'] = 1;
$v1['identity']['auth'] = ['algorithm' => 'legacy', 'private_key' => 'x', 'public_key' => 'y'];
$v1['state']['pending_event'] = ['signed_event' => ['legacy' => true], 'transports' => ['api']];
$store->write($v1);
$migrated = $setup->initialize([]);
ok($migrated['schema'] === 2, 'schema 1 migrates automatically to schema 2');
ok(!isset($migrated['identity']['auth']), 'migration removes obsolete installation keys');
ok(!isset($migrated['state']['pending_event']), 'migration drops obsolete signed pending event');
ok($migrated['identity']['installation_id'] === $readBack['identity']['installation_id'], 'migration preserves installation id');

function rrmdir($dir) {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) rrmdir($path); else unlink($path);
    }
    rmdir($dir);
}
rrmdir($root);
echo "SMOKE PASS\n";
