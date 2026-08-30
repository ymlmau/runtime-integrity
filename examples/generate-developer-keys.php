<?php
/**
 * Documentation helper. It is intentionally NOT exposed as a Composer bin command.
 * Run from the host application's root after Runtime Integrity is installed:
 * php vendor/ymlmau/runtime-integrity/examples/generate-developer-keys.php /secure/path
 */

use YmlMau\RuntimeIntegrity\Crypto\CryptoFactory;

$autoloadCandidates = [
    __DIR__ . '/../../../autoload.php',
    __DIR__ . '/../vendor/autoload.php',
];
foreach ($autoloadCandidates as $autoload) {
    if (is_file($autoload)) {
        require $autoload;
        break;
    }
}

$target = isset($argv[1]) ? $argv[1] : null;
if (!$target) {
    fwrite(STDERR, "Usage: php generate-developer-keys.php TARGET_DIRECTORY\n");
    exit(1);
}
if (!is_dir($target) && !@mkdir($target, 0700, true) && !is_dir($target)) {
    fwrite(STDERR, "Unable to create target directory.\n");
    exit(1);
}

$privatePath = rtrim($target, '/\\') . DIRECTORY_SEPARATOR . 'developer-private.key';
$publicPath = rtrim($target, '/\\') . DIRECTORY_SEPARATOR . 'developer-public.key';
$algorithmPath = rtrim($target, '/\\') . DIRECTORY_SEPARATOR . 'developer-algorithm.txt';
foreach ([$privatePath, $publicPath, $algorithmPath] as $path) {
    if (file_exists($path)) {
        fwrite(STDERR, "Refusing to overwrite existing key material: {$path}\n");
        exit(2);
    }
}

try {
    $provider = CryptoFactory::preferred();
    $pair = $provider->generateKeyPair();
    file_put_contents($privatePath, $pair['private_key']);
    file_put_contents($publicPath, $pair['public_key']);
    file_put_contents($algorithmPath, $provider->getAlgorithm() . PHP_EOL);
    @chmod($privatePath, 0600);
    @chmod($publicPath, 0644);
    @chmod($algorithmPath, 0644);
    fwrite(STDOUT, "KEYS_CREATED\n");
    fwrite(STDOUT, "ALGORITHM " . $provider->getAlgorithm() . PHP_EOL);
    fwrite(STDOUT, "PRIVATE {$privatePath}\n");
    fwrite(STDOUT, "PUBLIC {$publicPath}\n");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, '[runtime-integrity] ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
