<?php
namespace YmlMau\RuntimeIntegrity\Crypto;

final class OpenSslProvider implements SignatureProvider
{
    /** @var string|null */
    private $configPath;

    public function __construct($configPath = null)
    {
        $this->configPath = $configPath ?: $this->resolveConfigPath();
    }

    public static function isAvailable()
    {
        return self::isGenerationAvailable();
    }

    public static function isGenerationAvailable()
    {
        return function_exists('openssl_pkey_new')
            && function_exists('openssl_pkey_export')
            && function_exists('openssl_pkey_get_details')
            && function_exists('openssl_sign')
            && function_exists('openssl_verify');
    }

    public static function isVerificationAvailable()
    {
        return function_exists('openssl_verify');
    }

    public function generateKeyPair()
    {
        $options = [
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ];
        if ($this->configPath !== null) {
            $options['config'] = $this->configPath;
        }

        $this->clearErrors();
        $resource = openssl_pkey_new($options);
        if ($resource === false) {
            throw new \RuntimeException($this->buildFailureMessage('Unable to generate OpenSSL key pair.'));
        }

        $private = '';
        $exportOptions = [];
        if ($this->configPath !== null) {
            $exportOptions['config'] = $this->configPath;
        }
        if (!openssl_pkey_export($resource, $private, null, $exportOptions)) {
            throw new \RuntimeException($this->buildFailureMessage('Unable to export OpenSSL private key.'));
        }

        $details = openssl_pkey_get_details($resource);
        if (!is_array($details) || empty($details['key'])) {
            throw new \RuntimeException($this->buildFailureMessage('Unable to export OpenSSL public key.'));
        }

        return [
            'public_key' => base64_encode($details['key']),
            'private_key' => base64_encode($private),
        ];
    }

    public function sign($message, $privateKey)
    {
        $privateKey = $this->decodeKey($privateKey);
        $signature = '';
        $this->clearErrors();
        if (!openssl_sign($message, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException($this->buildFailureMessage('Unable to sign payload with OpenSSL.'));
        }
        return base64_encode($signature);
    }

    public function verify($message, $signature, $publicKey)
    {
        $sig = base64_decode($signature, true);
        if ($sig === false) {
            return false;
        }
        $publicKey = $this->decodeKey($publicKey);
        return openssl_verify($message, $sig, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    public function getAlgorithm()
    {
        return 'rsa-sha256';
    }

    public function getConfigPath()
    {
        return $this->configPath;
    }

    private function decodeKey($key)
    {
        if (strpos($key, '-----BEGIN') === 0) {
            return $key;
        }
        $decoded = base64_decode($key, true);
        if ($decoded === false) {
            throw new \RuntimeException('Invalid OpenSSL key encoding.');
        }
        return $decoded;
    }

    private function resolveConfigPath()
    {
        $candidates = [];

        foreach (['OPENSSL_CONF', 'SSLEAY_CONF'] as $variable) {
            $value = getenv($variable);
            if (is_string($value) && $value !== '') {
                $candidates[] = $value;
            }
        }

        $phpDir = dirname(PHP_BINARY);
        $candidates[] = $phpDir . DIRECTORY_SEPARATOR . 'extras' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'openssl.cnf';
        $candidates[] = $phpDir . DIRECTORY_SEPARATOR . 'extras' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'openssl.conf';
        $candidates[] = $phpDir . DIRECTORY_SEPARATOR . 'extras' . DIRECTORY_SEPARATOR . 'openssl' . DIRECTORY_SEPARATOR . 'openssl.cnf';
        $candidates[] = $phpDir . DIRECTORY_SEPARATOR . 'openssl.cnf';
        $candidates[] = $phpDir . DIRECTORY_SEPARATOR . 'openssl.conf';
        $candidates[] = '/etc/ssl/openssl.cnf';
        $candidates[] = '/usr/lib/ssl/openssl.cnf';
        $candidates[] = '/usr/local/ssl/openssl.cnf';

        if (DIRECTORY_SEPARATOR === '\\') {
            $programFiles = getenv('ProgramFiles');
            $programFilesX86 = getenv('ProgramFiles(x86)');
            foreach ([$programFiles, $programFilesX86] as $base) {
                if (is_string($base) && $base !== '') {
                    $candidates[] = $base . DIRECTORY_SEPARATOR . 'Common Files' . DIRECTORY_SEPARATOR . 'SSL' . DIRECTORY_SEPARATOR . 'openssl.cnf';
                    $candidates[] = $base . DIRECTORY_SEPARATOR . 'Common Files' . DIRECTORY_SEPARATOR . 'SSL' . DIRECTORY_SEPARATOR . 'openssl.conf';
                }
            }
        }

        // Package-local compatibility fallback. It is static package data, not
        // installation state, and exists only to let OpenSSL generate RSA keys.
        $candidates[] = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'openssl.cnf';

        foreach (array_unique($candidates) as $candidate) {
            if (is_string($candidate) && $candidate !== '' && is_file($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function clearErrors()
    {
        while (openssl_error_string() !== false) {
            // Drain stale errors so diagnostics belong to the current operation.
        }
    }

    private function buildFailureMessage($prefix)
    {
        $errors = [];
        while (($error = openssl_error_string()) !== false) {
            $errors[] = $error;
        }

        $message = $prefix . ' Config: ' . ($this->configPath ?: 'not found');
        if ($errors) {
            $message .= ' OpenSSL: ' . implode(' | ', $errors);
        }
        return $message;
    }
}
