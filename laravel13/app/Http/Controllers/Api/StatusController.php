<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RpcService;
use Web3\Contracts\Ethabi;
use Web3\Utils;

class StatusController extends Controller
{
    public function index()
    {
        $eventSignature = 'UserRegistered(address,address)';

        $rpc = RpcService::getInstance();

        try {
            $contractAddress = readContractAddress();
            if (!$contractAddress) {
                throw new \Exception(t('contract_not_deployed'));
            }

            $accounts = $rpc->call('eth_accounts');
            $admin = $accounts[0] ?? '';

            // 读取代币名称
            $nameSig = substr(Utils::sha3('name()'), 0, 10);
            $nameResult = $rpc->call('eth_call', [['to' => $contractAddress, 'data' => $nameSig], 'latest']);
            $name = decodeString($nameResult);

            // 读取代币符号
            $symbolSig = substr(Utils::sha3('symbol()'), 0, 10);
            $symbolResult = $rpc->call('eth_call', [['to' => $contractAddress, 'data' => $symbolSig], 'latest']);
            $symbol = decodeString($symbolResult);

            // 读取总供应量
            $totalSupplySig = substr(Utils::sha3('totalSupply()'), 0, 10);
            $totalSupplyResult = $rpc->call('eth_call', [['to' => $contractAddress, 'data' => $totalSupplySig], 'latest']);
            $totalSupply = hexdec($totalSupplyResult) / 10**18;

            // 读取每次邀请奖励
            $inviteRewardSig = substr(Utils::sha3('inviteReward()'), 0, 10);
            $inviteRewardResult = $rpc->call('eth_call', [['to' => $contractAddress, 'data' => $inviteRewardSig], 'latest']);
            $inviteReward = hexdec($inviteRewardResult) / 10**18;

            // 读取推荐比例
            $referralLevels = [];
            for ($i = 0; $i < 3; $i++) {
                $data = encodeCall('referralLevels', ['uint256'], [$i]);
                $result = $rpc->call('eth_call', [['to' => $contractAddress, 'data' => $data], 'latest']);
                $referralLevels[] = hexdec($result);
            }

            // 获取最新区块信息
            $latestBlockNumber = $rpc->call('eth_blockNumber');
            $latestBlock = $rpc->call('eth_getBlockByNumber', [$latestBlockNumber, false]);
            $blockInfo = [];
            if ($latestBlock) {
                // 如果最新块没有交易（dev chain 空闲时会连续出空块），
                // 往回找最近一个有交易的块，用它的 transactions / gasUsed 来显示
                $txCount = isset($latestBlock['transactions']) ? count($latestBlock['transactions']) : 0;
                $gasUsed = isset($latestBlock['gasUsed']) ? hexdec($latestBlock['gasUsed']) : 0;

                if ($txCount === 0 && hexdec($latestBlockNumber) > 0) {
                    // dev chain 空闲时会连续出很多空块，
                    // 扫 2000 块找最近有交易的（轻量 RPC 约 0.5s），找不到就返回空块真实值
                    $scanLimit = min(hexdec($latestBlockNumber), 2000);
                    $foundBlockNum = null;
                    for ($i = 1; $i <= $scanLimit; $i++) {
                        $prevNum = '0x' . dechex(hexdec($latestBlockNumber) - $i);
                        $txCountHex = $rpc->call('eth_getBlockTransactionCountByNumber', [$prevNum]);
                        if ($txCountHex && hexdec($txCountHex) > 0) {
                            $foundBlockNum = $prevNum;
                            $txCount = hexdec($txCountHex);
                            break;
                        }
                    }
                    if ($foundBlockNum) {
                        $txBlock = $rpc->call('eth_getBlockByNumber', [$foundBlockNum, false]);
                        if ($txBlock) {
                            $gasUsed = isset($txBlock['gasUsed']) ? hexdec($txBlock['gasUsed']) : 0;
                        }
                    }
                }

                $blockInfo = [
                    'number' => isset($latestBlock['number']) ? hexdec($latestBlock['number']) : null,
                    'hash' => $latestBlock['hash'] ?? null,
                    'timestamp' => isset($latestBlock['timestamp']) ? hexdec($latestBlock['timestamp']) : null,
                    'transactions' => $txCount,
                    'gas_used' => $gasUsed,
                    'gas_limit' => isset($latestBlock['gasLimit']) ? hexdec($latestBlock['gasLimit']) : 0,
                    'size' => isset($latestBlock['size']) ? hexdec($latestBlock['size']) : 0,
                    'miner' => $latestBlock['miner'] ?? null
                ];
            }

            // 统计注册钱包数量
            $topic = Utils::sha3($eventSignature);
            $logs = $rpc->call('eth_getLogs', [[
                'address' => $contractAddress,
                'fromBlock' => '0x0',
                'toBlock' => $latestBlockNumber ?? 'latest',
                'topics' => [$topic]
            ]]);
            $walletCount = is_array($logs) ? count($logs) : 0;

            return response()->json([
                'success' => true,
                'contract_address' => $contractAddress,
                'admin' => $admin,
                'name' => $name,
                'symbol' => $symbol,
                'total_supply' => $totalSupply,
                'invite_reward' => $inviteReward,
                'referral_levels' => $referralLevels,
                'wallet_count' => $walletCount,
                'block' => $blockInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
