<?php
namespace YmlMau\RuntimeIntegrity\Crypto;

interface SignatureProvider
{
    public function generateKeyPair();
    public function sign($message, $privateKey);
    public function verify($message, $signature, $publicKey);
    public function getAlgorithm();
}
