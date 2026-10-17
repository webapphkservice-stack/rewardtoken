<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RpcService;

class NodeController extends Controller
{
    /**
     * geth 启动参数模板（%s = --datadir 路径，指向 storage/geth，账本随项目迁移）
     * geth 1.17 已移除 --mine/--allow-insecure-unlock/--rpc.enabledeprecatedpersonal：
     * dev 模式默认 auto-mine 且账户自动解锁
     */
    private const GETH_ARGS = '--dev --datadir "%s" --http --http.api eth,net,admin --http.port 8545 --http.addr 0.0.0.0';

    private function gethCommand(): string
    {
        return 'geth ' . sprintf(self::GETH_ARGS, storage_path('geth'));
    }

    private function gethLog(): string
    {
        return storage_path('geth/geth.log');
    }

    public function index()
    {
        $rpc = RpcService::getInstance();
        $rpcUrl = 'http://localhost:8545';
        $action = request()->query('action', request()->post('action', 'status'));

        try {
            switch ($action) {
                case 'status':
                    $running = $this->isGethRunning();
                    $status = [
                        'running' => $running,
                        'pid' => $this->getGethPid()
                    ];

                    if ($running) {
                        $blockNumber = $this->rpcCall($rpc, 'eth_blockNumber');
                        $peerCount = $this->rpcCall($rpc, 'net_peerCount');
                        $accounts = $this->rpcCall($rpc, 'eth_accounts');
                        $status['block_number'] = $blockNumber ? hexdec($blockNumber) : null;
                        $status['peer_count'] = $peerCount ? hexdec($peerCount) : 0;
                        $status['accounts'] = $accounts ?? [];
                        $status['rpc_url'] = $rpcUrl;
                        $status['syncing'] = $this->rpcCall($rpc, 'eth_syncing') ?? false;
                    }

                    return response()->json([
                        'success' => true,
                        'data' => $status
                    ]);

                case 'start':
                    if ($this->isGethRunning()) {
                        return response()->json(['success' => false, 'message' => t('geth_already_running')]);
                    }

                    $cmd = $this->gethCommand() . ' > ' . $this->gethLog() . ' 2>&1 &';
                    shell_exec($cmd);

                    // 等待启动
                    for ($i = 0; $i < 10; $i++) {
                        sleep(1);
                        if ($this->isGethRunning()) {
                            break;
                        }
                    }

                    if ($this->isGethRunning()) {
                        return response()->json([
                            'success' => true,
                            'message' => t('geth_started'),
                            'pid' => $this->getGethPid()
                        ]);
                    }
                    return response()->json(['success' => false, 'message' => t('geth_start_failed')]);

                case 'stop':
                    if (!$this->isGethRunning()) {
                        return response()->json(['success' => false, 'message' => t('geth_not_running')]);
                    }

                    exec('pkill -f "geth --dev"', $output, $returnCode);
                    sleep(2);

                    if (!$this->isGethRunning()) {
                        return response()->json(['success' => true, 'message' => t('geth_stopped')]);
                    }
                    return response()->json(['success' => false, 'message' => t('geth_stop_failed')]);

                case 'restart':
                    exec('pkill -f "geth --dev"', $output, $returnCode);
                    sleep(2);

                    $cmd = $this->gethCommand() . ' > ' . $this->gethLog() . ' 2>&1 &';
                    shell_exec($cmd);

                    for ($i = 0; $i < 10; $i++) {
                        sleep(1);
                        if ($this->isGethRunning()) {
                            break;
                        }
                    }

                    if ($this->isGethRunning()) {
                        return response()->json([
                            'success' => true,
                            'message' => t('geth_restarted'),
                            'pid' => $this->getGethPid()
                        ]);
                    }
                    return response()->json(['success' => false, 'message' => t('geth_restart_failed')]);

                default:
                    return response()->json(['success' => false, 'message' => t('unknown_action')]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function rpcCall(RpcService $rpc, string $method, array $params = [])
    {
        try {
            return $rpc->call($method, $params);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function isGethRunning(): bool
    {
        exec('pgrep -f "geth --dev"', $output, $returnCode);
        return !empty($output);
    }

    private function getGethPid(): ?string
    {
        exec('pgrep -f "geth --dev"', $output, $returnCode);
        return $output[0] ?? null;
    }
}
