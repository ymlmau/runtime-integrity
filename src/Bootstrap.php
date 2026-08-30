<?php
namespace YmlMau\RuntimeIntegrity;

use yii\base\BootstrapInterface;

final class Bootstrap implements BootstrapInterface
{
    public function bootstrap($app)
    {
        try {
            $vendorPath = isset($app->vendorPath) ? $app->vendorPath : null;
            if (!$vendorPath) {
                return;
            }
            $projectRoot = dirname($vendorPath);
            $store = new StateStore($projectRoot . DIRECTORY_SEPARATOR . '.runtime-integrity');
            $seed = $this->seedConfig($projectRoot, $app);
            $document = (new AutoSetup($store))->initialize($seed);
            $pulse = new Pulse($store, $projectRoot, $projectRoot . DIRECTORY_SEPARATOR . '.runtime-integrity.baseline');
            if (!$pulse->isDue($document)) {
                return;
            }
            register_shutdown_function(function () use ($pulse) {
                try {
                    if (function_exists('fastcgi_finish_request')) {
                        @fastcgi_finish_request();
                    }
                    $pulse->run();
                } catch (\Throwable $e) {
                    // Fail-open.
                }
            });
        } catch (\Throwable $e) {
            // Fail-open. A present package must never break the host application.
        }
    }

    private function seedConfig($projectRoot, $app)
    {
        $seed = [];
        $composerPath = $projectRoot . DIRECTORY_SEPARATOR . 'composer.json';
        if (is_file($composerPath)) {
            $raw = @file_get_contents($composerPath);
            $composer = $raw !== false ? json_decode($raw, true) : null;
            if (is_array($composer) && !empty($composer['extra']['ymlmau-runtime-integrity']) && is_array($composer['extra']['ymlmau-runtime-integrity'])) {
                $seed = $composer['extra']['ymlmau-runtime-integrity'];
            }
        }
        if (empty($seed['product_id']) && isset($app->id) && is_string($app->id) && $app->id !== '') {
            $seed['product_id'] = $app->id;
        }
        return $seed;
    }
}
