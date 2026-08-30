<?php
namespace YmlMau\RuntimeIntegrity\Integrity;

use YmlMau\RuntimeIntegrity\Support\CanonicalJson;
use YmlMau\RuntimeIntegrity\Crypto\SodiumEd25519Provider;
use YmlMau\RuntimeIntegrity\Crypto\OpenSslProvider;

final class Baseline
{
    public static function load($path)
    {
        if (!is_file($path)) {
            throw new \RuntimeException('Baseline file is missing.');
        }
        $raw = @file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException('Unable to read baseline file.');
        }
        if (substr($raw, 0, 2) === "\x1f\x8b" && function_exists('gzdecode')) {
            $decoded = @gzdecode($raw);
            if ($decoded !== false) {
                $raw = $decoded;
            }
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Baseline file is invalid JSON.');
        }
        return $data;
    }

    public static function verify(array $baseline, $trustedAlgorithm, $trustedPublicKey)
    {
        if (empty($baseline['payload']) || !is_array($baseline['payload']) || empty($baseline['signature']) || !is_array($baseline['signature'])) {
            return false;
        }
        $algorithm = isset($baseline['signature']['algorithm']) ? $baseline['signature']['algorithm'] : null;
        $signature = isset($baseline['signature']['value']) ? $baseline['signature']['value'] : null;
        if (!$algorithm || !$signature || !$trustedPublicKey || !hash_equals((string) $trustedAlgorithm, (string) $algorithm)) {
            return false;
        }
        $message = CanonicalJson::encode($baseline['payload']);
        if ($algorithm === 'ed25519' && SodiumEd25519Provider::isAvailable()) {
            return (new SodiumEd25519Provider())->verify($message, $signature, $trustedPublicKey);
        }
        if ($algorithm === 'rsa-sha256' && OpenSslProvider::isVerificationAvailable()) {
            return (new OpenSslProvider())->verify($message, $signature, $trustedPublicKey);
        }
        return false;
    }

    public static function buildPayload($productId, $buildId, array $files)
    {
        ksort($files, SORT_STRING);
        $rootMaterial = '';
        foreach ($files as $path => $hash) {
            $rootMaterial .= $path . "\0" . $hash . "\n";
        }
        return [
            'schema' => 1,
            'product_id' => $productId,
            'build_id' => $buildId,
            'generated_at' => gmdate('c'),
            'file_count' => count($files),
            'root_hash' => 'sha256:' . hash('sha256', $rootMaterial),
            'files' => $files,
        ];
    }
}
