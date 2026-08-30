<?php
namespace YmlMau\RuntimeIntegrity;

final class Config
{
    const SCHEMA_VERSION = 2;
    const MONITOR_VERSION = '1.1.3';

    public static function defaults()
    {
        return [
            'product_id' => null,
            'developer_auth' => [
                'algorithm' => null,
                'public_key' => null,
            ],
            'email' => [
                'enabled' => false,
                'relay_url' => null,
            ],
            'api' => [
                'enabled' => false,
                'url' => null,
            ],
            'privacy' => [
                'include_hostname' => false,
            ],
            'manifest' => [
                'include' => ['composer.json', 'composer.lock', 'yii', 'yii.bat', 'common', 'frontend', 'backend', 'console', 'controllers', 'models', 'components', 'helpers', 'services', 'modules', 'views', 'widgets', 'commands', 'config', 'assets', 'web'],
                'exclude' => ['vendor', 'runtime', 'web/assets', 'web/debug', 'web/uploads', 'uploads', 'cache', 'logs', 'sessions', 'frontend/runtime', 'frontend/web/assets', 'frontend/web/debug', 'frontend/web/uploads', 'backend/runtime', 'backend/web/assets', 'backend/web/debug', 'backend/web/uploads', 'console/runtime', 'common/runtime', '.git', 'node_modules', '.env', '.env.local', 'config/local.php', 'config/*-local.php', '*.log', '*.tmp', '*.cache', '*.bak', '*.swp', '.DS_Store', 'Thumbs.db', '.runtime-integrity', '.runtime-integrity*', '.runtime-integrity.baseline'],
            ],
        ];
    }

    public static function merge(array $base, array $override)
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = self::merge($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }

    public static function refreshPackagePolicy(array $runtimeConfig, array $seedConfig)
    {
        $current = self::merge(self::defaults(), $seedConfig);
        $runtimeConfig['manifest'] = $current['manifest'];
        return $runtimeConfig;
    }

    public static function validateTransport(array $transport, $urlKey)
    {
        $enabled = !empty($transport['enabled']);
        $url = isset($transport[$urlKey]) ? $transport[$urlKey] : null;
        if (!$enabled && ($url === null || $url === '')) {
            return true;
        }
        if ($enabled && is_string($url) && filter_var($url, FILTER_VALIDATE_URL)) {
            return true;
        }
        return false;
    }
}
