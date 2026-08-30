<?php
namespace YmlMau\RuntimeIntegrity\Crypto;

final class CryptoFactory
{
    public static function preferred()
    {
        if (SodiumEd25519Provider::isAvailable()) {
            return new SodiumEd25519Provider();
        }
        if (OpenSslProvider::isAvailable()) {
            return new OpenSslProvider();
        }
        throw new \RuntimeException('No supported cryptographic provider is available.');
    }

    public static function forAlgorithm($algorithm)
    {
        if ($algorithm === 'ed25519' && SodiumEd25519Provider::isAvailable()) {
            return new SodiumEd25519Provider();
        }
        if ($algorithm === 'rsa-sha256' && OpenSslProvider::isAvailable()) {
            return new OpenSslProvider();
        }
        throw new \RuntimeException('Configured cryptographic provider is unavailable: ' . $algorithm);
    }
}
