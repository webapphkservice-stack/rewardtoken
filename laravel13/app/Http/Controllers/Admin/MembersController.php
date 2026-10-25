<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RpcService;
use Web3\Utils;

class MembersController extends Controller
{
    public function index()
    {
        $accountsFile = storage_path('accounts.json');
        $accounts = is_file($accountsFile)
            ? (json_decode((string) file_get_contents($accountsFile), true) ?: [])
            : [];

        // 按地址聚合购买订单（storage/orders.json）
        $ordersFile = storage_path('orders.json');
        $allOrders = is_file($ordersFile)
            ? (json_decode((string) file_get_contents($ordersFile), true) ?: [])
            : [];
        $orderStats = [];
        foreach ($allOrders as $o) {
            if (!is_array($o)) {
                continue;
            }
            $addr = strtolower(trim((string) ($o['address'] ?? '')));
            if ($addr === '') {
                continue;
            }
            if (!isset($orderStats[$addr])) {
                $orderStats[$addr] = ['count' => 0, 'total_usdt' => 0.0];
            }
            $orderStats[$addr]['count']++;
            $orderStats[$addr]['total_usdt'] += (float) ($o['total_usdt'] ?? 0);
        }

        // 链上代币余额（best-effort：合约未部署或节点不可达时显示占位符）
        $balances = $this->loadBalances(array_keys($accounts));

        $members = [];
        foreach ($accounts as $address => $acc) {
            if (!is_array($acc)) {
                continue;
            }
            $addr = strtolower((string) $address);
            $members[] = [
                'address' => $addr,
                'username' => (string) ($acc['username'] ?? ''),
                'inviter' => (string) ($acc['inviter'] ?? ''),
                'registered_at' => (int) ($acc['registered_at'] ?? 0),
                'balance' => $balances[$addr] ?? null,
                'order_count' => $orderStats[$addr]['count'] ?? 0,
                'total_usdt' => $orderStats[$addr]['total_usdt'] ?? 0.0,
            ];
        }
        usort($members, fn ($a, $b) => $b['registered_at'] <=> $a['registered_at']);

        return view('admin.members', [
            'members' => $members,
            'totalMembers' => count($members),
            'totalPurchasedUsdt' => array_sum(array_column($members, 'total_usdt')),
        ]);
    }

    /**
     * 批量读取各地址代币余额（balanceOf），失败时返回空数组。
     */
    private function loadBalances(array $addresses): array
    {
        $result = [];
        if (!$addresses) {
            return $result;
        }
        $contractAddress = readContractAddress();
        if (!$contractAddress) {
            return $result;
        }
        try {
            $rpc = RpcService::getInstance();
            foreach ($addresses as $address) {
                $addr = strtolower((string) $address);
                try {
                    $data = encodeCall('balanceOf', ['address'], [$addr]);
                    $raw = $rpc->call('eth_call', [[
                        'to' => $contractAddress,
                        'data' => $data,
                    ], 'latest']);
                    if (is_string($raw) && $raw !== '' && $raw !== '0x') {
                        $wei = (string) Utils::toBn($raw)->toString();
                        $result[$addr] = $this->weiToToken($wei);
                    } else {
                        $result[$addr] = '0';
                    }
                } catch (\Throwable $e) {
                    // 单个地址失败不影响整体展示
                    continue;
                }
            }
        } catch (\Throwable $e) {
            return [];
        }
        return $result;
    }

    private function weiToToken(string $wei): string
    {
        $w = ltrim($wei, '0');
        $w = $w === '' ? '0' : $w;
        $intPart = strlen($w) > 18 ? substr($w, 0, -18) : '0';
        $decPart = strlen($w) > 18 ? substr($w, -18) : str_pad($w, 18, '0', STR_PAD_LEFT);
        $dec = rtrim(substr($decPart, 0, 8), '0');
        return $dec === '' ? $intPart : $intPart . '.' . $dec;
    }
}
