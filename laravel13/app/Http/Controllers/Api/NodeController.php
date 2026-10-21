<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RpcService;

class NodeController extends Controller
{
    /**
     * geth 启动参数模板（%s = --datadir 路径，指向 storage/geth，账本随项目迁移）
     * 仅用于无 systemd 的环境（本地开发）手动拉起；生产由 geth.service 管理
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

                    if ($this->startGeth()) {
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

                    if ($this->stopGeth()) {
                        return response()->json(['success' => true, 'message' => t('geth_stopped')]);
                    }
                    return response()->json(['success' => false, 'message' => t('geth_stop_failed')]);

                case 'restart':
                    $this->stopGeth();

                    if ($this->startGeth()) {
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

    /**
     * 进程检测：按可执行文件名精确匹配（pgrep -x）。
     * 旧实现 pgrep -f "geth --dev" 依赖启动参数顺序，生产 systemd
     * 命令行为 "geth --datadir ... --dev"，无法匹配导致状态误报。
     */
    private function isGethRunning(): bool
    {
        exec('pgrep -x geth', $output);
        return !empty($output);
    }

    private function getGethPid(): ?string
    {
        exec('pgrep -x geth', $output);
        return $output[0] ?? null;
    }

    /**
     * 是否存在 geth 的 systemd 单元（生产环境由 geth.service 托管）
     */
    private function hasSystemdUnit(): bool
    {
        exec('systemctl cat geth >/dev/null 2>&1', $output, $returnCode);
        return $returnCode === 0;
    }

    private function startGeth(): bool
    {
        if ($this->hasSystemdUnit()) {
            exec('sudo -n systemctl start geth 2>&1', $out, $rc);
            if ($rc !== 0) {
                return false;
            }
        } else {
            $cmd = $this->gethCommand() . ' > ' . $this->gethLog() . ' 2>&1 &';
            shell_exec($cmd);
        }

        for ($i = 0; $i < 10; $i++) {
            sleep(1);
            if ($this->isGethRunning()) {
                return true;
            }
        }

        return $this->isGethRunning();
    }

    private function stopGeth(): bool
    {
        if ($this->hasSystemdUnit()) {
            exec('sudo -n systemctl stop geth 2>&1');
        } else {
            exec('pkill -x geth');
        }

        sleep(2);
        return !$this->isGethRunning();
    }
}
