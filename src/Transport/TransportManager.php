<?php
namespace YmlMau\RuntimeIntegrity\Transport;

final class TransportManager
{
    public function buildFromConfig(array $config)
    {
        $transports = [];
        if (!empty($config['email']['enabled']) && !empty($config['email']['relay_url']) && filter_var($config['email']['relay_url'], FILTER_VALIDATE_URL)) {
            $transports[] = new HttpJsonTransport('email', $config['email']['relay_url']);
        }
        if (!empty($config['api']['enabled']) && !empty($config['api']['url']) && filter_var($config['api']['url'], FILTER_VALIDATE_URL)) {
            $transports[] = new HttpJsonTransport('api', $config['api']['url']);
        }
        return $transports;
    }
}
