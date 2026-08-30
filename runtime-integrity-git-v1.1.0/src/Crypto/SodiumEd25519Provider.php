<?php
namespace YmlMau\RuntimeIntegrity\Crypto;

final class SodiumEd25519Provider implements SignatureProvider
{
    public static function isAvailable()
    {
        return function_exists('sodium_crypto_sign_keypair') && function_exists('sodium_crypto_sign_detached');
    }

    public function generateKeyPair()
    {
        $pair = sodium_crypto_sign_keypair();
        return [
            'public_key' => base64_encode(sodium_crypto_sign_publickey($pair)),
            'private_key' => base64_encode(sodium_crypto_sign_secretkey($pair)),
        ];
    }

    public function sign($message, $privateKey)
    {
        $raw = base64_decode($privateKey, true);
        if ($raw === false) {
            throw new \RuntimeException('Invalid private key encoding.');
        }
        return base64_encode(sodium_crypto_sign_detached($message, $raw));
    }

    public function verify($message, $signature, $publicKey)
    {
        $sig = base64_decode($signature, true);
        $pub = base64_decode($publicKey, true);
        if ($sig === false || $pub === false) {
            return false;
        }
        return sodium_crypto_sign_verify_detached($sig, $message, $pub);
    }

    public function getAlgorithm()
    {
        return 'ed25519';
    }
}
