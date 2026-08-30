<?php
namespace YmlMau\RuntimeIntegrity;

final class EnvironmentFingerprint
{
    public static function create($projectRoot)
    {
        $parts = [
            php_uname('n'),
            PHP_OS_FAMILY,
            realpath($projectRoot) ?: $projectRoot,
        ];
        return 'sha256:' . hash('sha256', implode("\n", $parts));
    }
}
