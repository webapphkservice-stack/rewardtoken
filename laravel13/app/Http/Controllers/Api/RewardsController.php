<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RpcService;
use Web3\Utils;

class RewardsController extends Controller
{
    public function index()
    {
        $rpc = RpcService::getInstance();

        // 过滤地址：连续登录用户地址（前端传入 loginAddress）
        $filterAddress = request()->query('address', '');
        if (is_string($filterAddress)) {
            $filterAddress = trim($filterAddress);
        }

        try {
            $contractAddress = readContractAddress();
            if (!$contractAddress) {
                throw new \Exception(t('contract_not_deployed'));
            }

            // RewardDistributed(address indexed inviter, uint256 amount, uint256 level)
            $eventSignature = 'RewardDistributed(address,uint256,uint256)';
            $topic = Utils::sha3($eventSignature);

            // 获取当前区块号
            $latestBlock = $rpc->call('eth_blockNumber');
            $fromBlock = '0x0';
            $toBlock = $latestBlock ?? 'latest';

            $logs = $rpc->call('eth_getLogs', [[
                'address' => $contractAddress,
                'fromBlock' => $fromBlock,
                'toBlock' => $toBlock,
                'topics' => [$topic]
            ]]);

            $rewardsFound = [];
            foreach ($logs as $log) {
                $inviter = '0x' . substr($log['topics'][1], 26);
                // 仅保留当前登录用户（inviter）的奖励记录
                if ($filterAddress && strcasecmp($inviter, $filterAddress) !== 0) {
                    continue;
                }
                // data 去掉 0x 后，前 64 个 hex 字符（32 字节）为 amount，后 64 个为 level
                $dataHex = substr($log['data'], 2);
                $amount = hexdec(substr($dataHex, 0, 64)) / 10**18;
                $level = hexdec(substr($dataHex, 64, 64));
                $blockNumber = hexdec($log['blockNumber']);
                $txHash = $log['transactionHash'];

                $rewardsFound[] = [
                    'inviter' => $inviter,
                    'amount' => $amount,
                    'level' => $level,
                    'block_number' => $blockNumber,
                    'tx_hash' => $txHash
                ];
            }

            $rewards = $rewardsFound;

            // 按区块号降序
            usort($rewards, function ($a, $b) {
                return $b['block_number'] - $a['block_number'];
            });

            return response()->json([
                'success' => true,
                'data' => $rewards
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
