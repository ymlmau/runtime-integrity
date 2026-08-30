<?php
namespace YmlMau\RuntimeIntegrity\Integrity;

final class IntegrityChecker
{
    public function scan($projectRoot, array $include, array $exclude)
    {
        $files = [];
        foreach ($include as $relative) {
            $absolute = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($relative, '/\\');
            if (is_file($absolute)) {
                if (!$this->isExcluded($relative, $exclude)) {
                    $files[$this->normalize($relative)] = 'sha256:' . hash_file('sha256', $absolute);
                }
                continue;
            }
            if (!is_dir($absolute)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($absolute, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }
                $full = $file->getPathname();
                $rel = ltrim(substr($full, strlen(rtrim($projectRoot, DIRECTORY_SEPARATOR))), '/\\');
                $rel = $this->normalize($rel);
                if ($this->isExcluded($rel, $exclude)) {
                    continue;
                }
                $files[$rel] = 'sha256:' . hash_file('sha256', $full);
            }
        }
        ksort($files, SORT_STRING);
        return $files;
    }

    public function compare(array $expected, array $observed)
    {
        $modified = [];
        $deleted = [];
        $added = [];
        foreach ($expected as $path => $hash) {
            if (!array_key_exists($path, $observed)) {
                $deleted[] = $path;
            } elseif (!hash_equals($hash, $observed[$path])) {
                $modified[] = $path;
            }
        }
        foreach ($observed as $path => $hash) {
            if (!array_key_exists($path, $expected)) {
                $added[] = $path;
            }
        }
        sort($modified, SORT_STRING);
        sort($deleted, SORT_STRING);
        sort($added, SORT_STRING);
        return [
            'clean' => !$modified && !$deleted && !$added,
            'modified' => $modified,
            'deleted' => $deleted,
            'added' => $added,
        ];
    }

    public function rootHash(array $files)
    {
        ksort($files, SORT_STRING);
        $material = '';
        foreach ($files as $path => $hash) {
            $material .= $path . "\0" . $hash . "\n";
        }
        return 'sha256:' . hash('sha256', $material);
    }

    private function isExcluded($path, array $exclude)
    {
        $path = $this->normalize($path);
        foreach ($exclude as $pattern) {
            $pattern = $this->normalize($pattern);
            if ($pattern === $path || strpos($path, rtrim($pattern, '/') . '/') === 0) {
                return true;
            }
            if (function_exists('fnmatch') && fnmatch($pattern, $path, FNM_PATHNAME)) {
                return true;
            }
        }
        return false;
    }

    private function normalize($path)
    {
        return str_replace('\\', '/', trim($path, '/\\'));
    }
}
