<?php
namespace YmlMau\RuntimeIntegrity\Support;

final class CanonicalJson
{
    public static function encode($value)
    {
        $normalized = self::normalize($value);
        $json = json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \RuntimeException('Unable to encode canonical JSON: ' . json_last_error_msg());
        }
        return $json;
    }

    private static function normalize($value)
    {
        if (is_array($value)) {
            if (self::isAssoc($value)) {
                ksort($value, SORT_STRING);
                foreach ($value as $key => $item) {
                    $value[$key] = self::normalize($item);
                }
            } else {
                foreach ($value as $key => $item) {
                    $value[$key] = self::normalize($item);
                }
            }
        }
        return $value;
    }

    private static function isAssoc(array $array)
    {
        if ($array === []) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
