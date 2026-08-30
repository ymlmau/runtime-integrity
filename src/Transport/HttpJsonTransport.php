<?php
namespace YmlMau\RuntimeIntegrity\Transport;

final class HttpJsonTransport implements TransportInterface
{
    private $name;
    private $url;
    private $timeout;

    public function __construct($name, $url, $timeout = 2)
    {
        $this->name = $name;
        $this->url = $url;
        $this->timeout = $timeout;
    }

    public function getName()
    {
        return $this->name;
    }

    public function send(array $event)
    {
        $json = json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \RuntimeException('Unable to encode report payload.');
        }

        if (function_exists('curl_init')) {
            return $this->sendWithCurl($json);
        }

        if ((bool) ini_get('allow_url_fopen')) {
            return $this->sendWithStream($json);
        }

        throw new \RuntimeException($this->name . ' transport has no HTTP client available.');
    }

    private function sendWithCurl($json)
    {
        $handle = curl_init($this->url);
        if ($handle === false) {
            throw new \RuntimeException($this->name . ' transport could not initialize cURL.');
        }

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: YmlMau-Runtime-Integrity/1.1.0',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        $result = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($result === false || $status < 200 || $status >= 300) {
            $suffix = $error !== '' ? ': ' . $error : ' (HTTP ' . $status . ')';
            throw new \RuntimeException($this->name . ' transport failed' . $suffix . '.');
        }
        return true;
    }

    private function sendWithStream($json)
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\nUser-Agent: YmlMau-Runtime-Integrity/1.1.0\r\n",
                'content' => $json,
                'timeout' => $this->timeout,
                'ignore_errors' => true,
                'follow_location' => 0,
            ],
        ]);
        $result = @file_get_contents($this->url, false, $context);
        if ($result === false) {
            throw new \RuntimeException($this->name . ' transport failed.');
        }
        $status = 0;
        if (isset($http_response_header) && isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $status = (int) $m[1];
        }
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException($this->name . ' transport returned HTTP ' . $status . '.');
        }
        return true;
    }
}
