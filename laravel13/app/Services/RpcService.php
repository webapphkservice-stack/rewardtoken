<?php

namespace App\Services;

class RpcService
{
    private static $instance = null;

    private $rpcUrl;
    private $timeout;

    private function __construct(string $rpcUrl, int $timeout)
    {
        $this->rpcUrl = $rpcUrl;
        $this->timeout = $timeout;
    }

    public static function getInstance(?string $rpcUrl = null, ?int $timeout = null): self
    {
        if (self::$instance === null) {
            $config = self::loadConfig();
            $rpcUrl = $rpcUrl ?? $config['rpc_url'] ?? 'http://localhost:8545';
            $timeout = $timeout ?? $config['rpc_timeout'] ?? 30;
            self::$instance = new self($rpcUrl, $timeout);
        }
        return self::$instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    public function call(string $method, array $params = [])
    {
        $payload = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => time() + rand(0, 1000),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->rpcUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            throw new \Exception('RPC connection error: ' . $curlError);
        }

        $result = json_decode($response, true);
        if (isset($result['error'])) {
            throw new \Exception('RPC error: ' . json_encode($result['error']));
        }

        return $result['result'] ?? null;
    }

    public function getRpcUrl(): string
    {
        return $this->rpcUrl;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    private static function loadConfig(): array
    {
        $configFile = __DIR__ . '/../../config/site.json';
        if (!file_exists($configFile)) {
            return [];
        }

        $json = file_get_contents($configFile);
        $config = json_decode($json, true);
        return is_array($config) ? $config : [];
    }
}
