<?php
namespace YmlMau\RuntimeIntegrity;

use YmlMau\RuntimeIntegrity\Support\CanonicalJson;

final class StateStore
{
    private $path;
    private $lockPath;

    public function __construct($path)
    {
        $this->path = $path;
        $this->lockPath = $path . '.lock';
    }

    public function getPath()
    {
        return $this->path;
    }

    public function exists()
    {
        return is_file($this->path);
    }

    public function read()
    {
        if (!$this->exists()) {
            return null;
        }
        return $this->readPath($this->path);
    }

    public function initialize(array $data)
    {
        $lock = $this->openStateLock();
        try {
            if (!flock($lock, LOCK_EX)) {
                throw new \RuntimeException('Unable to lock runtime integrity state.');
            }
            if ($this->exists()) {
                return $this->readPath($this->path);
            }
            $this->writeUnlocked($data);
            return $data;
        } finally {
            if (is_resource($lock)) {
                @flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }

    public function write(array $data)
    {
        $lock = $this->openStateLock();
        try {
            if (!flock($lock, LOCK_EX)) {
                throw new \RuntimeException('Unable to lock runtime integrity state.');
            }
            $this->writeUnlocked($data);
        } finally {
            if (is_resource($lock)) {
                @flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }

    public function tryExclusiveLock()
    {
        $this->ensureDirectory();
        $handle = @fopen($this->path . '.pulse.lock', 'c');
        if ($handle === false) {
            return null;
        }
        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            return null;
        }
        return $handle;
    }

    public function releaseLock($handle)
    {
        if (is_resource($handle)) {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function openStateLock()
    {
        $this->ensureDirectory();
        $lock = @fopen($this->lockPath, 'c');
        if ($lock === false) {
            throw new \RuntimeException('Unable to open runtime integrity lock.');
        }
        return $lock;
    }

    private function ensureDirectory()
    {
        $dir = dirname($this->path);
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create runtime integrity state directory.');
        }
    }

    private function readPath($path)
    {
        $raw = @file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException('Unable to read runtime integrity state.');
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Runtime integrity state is invalid JSON.');
        }
        return $data;
    }

    private function writeUnlocked(array $data)
    {
        $tmp = $this->path . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
        $payload = CanonicalJson::encode($data) . PHP_EOL;
        if (@file_put_contents($tmp, $payload, LOCK_EX) === false) {
            throw new \RuntimeException('Unable to write runtime integrity temporary state.');
        }
        @chmod($tmp, 0600);
        if (!@rename($tmp, $this->path)) {
            @unlink($tmp);
            throw new \RuntimeException('Unable to atomically replace runtime integrity state.');
        }
        @chmod($this->path, 0600);
    }
}
