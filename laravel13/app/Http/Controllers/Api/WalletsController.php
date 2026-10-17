<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RpcService;
use Web3\Utils;

class WalletsController extends Controller
{
    public function index()
    {
        $rpc = RpcService::getInstance();

        try {
            $contractAddress = readContractAddress();
            if (!$contractAddress) {
                throw new \Exception(t('contract_not_deployed'));
            }

            $eventSignature = 'UserRegistered(address,address)';
            $topic = Utils::sha3($eventSignature);

            $latestBlock = $rpc->call('eth_blockNumber');
            $fromBlock = '0x0';
            $toBlock = $latestBlock ?? 'latest';

            $logs = $rpc->call('eth_getLogs', [[
                'address' => $contractAddress,
                'fromBlock' => $fromBlock,
                'toBlock' => $toBlock,
                'topics' => [$topic]
            ]]);

            $wallets = [];
            foreach ($logs as $log) {
                $user = '0x' . substr($log['topics'][1], 26);
                $inviter = '0x' . substr($log['topics'][2], 26);
                $wallets[] = [
                    'user' => $user,
                    'inviter' => $inviter,
                    'tx_hash' => $log['transactionHash'],
                    'block_number' => hexdec($log['blockNumber'])
                ];
            }

            return response()->json([
                'success' => true,
                'count' => count($wallets),
                'wallets' => $wallets
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
