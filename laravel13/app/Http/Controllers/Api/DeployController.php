<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RpcService;
use Web3\Contracts\Ethabi;

class DeployController extends Controller
{
    private function storageContractsDir(): string
    {
        return storage_path('contracts/compiled');
    }

    public function index()
    {
        $rpc = RpcService::getInstance();

        try {
            if (!request()->isMethod('POST')) {
                throw new \Exception(t('only_post_allowed'));
            }

            $existingAddress = readContractAddress();
            $force = intval(request()->post('force', 0));
            if ($existingAddress) {
                $code = $rpc->call('eth_getCode', [$existingAddress, 'latest']);
                if (!empty($code) && strlen($code) > 2 && !$force) {
                    throw new \Exception(t('contract_already_deployed'));
                }
            }

            $name = trim(request()->post('name', ''));
            $symbol = trim(request()->post('symbol', ''));
            $totalSupply = intval(request()->post('total_supply', 0));
            $inviteReward = intval(request()->post('invite_reward', 0));
            $level1 = intval(request()->post('level1', 0));
            $level2 = intval(request()->post('level2', 0));
            $level3 = intval(request()->post('level3', 0));
            $rewardPoolPercent = intval(request()->post('reward_pool_percent', 0));

            if (!$name || !$symbol) throw new \Exception(t('name_symbol_required'));
            if ($totalSupply <= 0) throw new \Exception(t('total_supply_must_positive'));
            if ($inviteReward <= 0) throw new \Exception(t('invite_reward_must_positive'));
            if ($level1 + $level2 + $level3 > 100) throw new \Exception(t('ratio_sum_error'));
            if ($rewardPoolPercent <= 0 || $rewardPoolPercent > 100) throw new \Exception(t('reward_pool_ratio_invalid'));

            $accounts = $rpc->call('eth_accounts');
            $admin = $accounts[0];

            $bytecode = '0x' . file_get_contents($this->storageContractsDir() . '/InviteRewardToken.bin');
            $abi = json_decode(file_get_contents($this->storageContractsDir() . '/InviteRewardToken.abi'), true);

            $ethabi = new Ethabi();
            $encodedParams = $ethabi->encodeParameters(
                ['string', 'string', 'uint256', 'uint256', 'uint256[3]', 'uint256'],
                [$name, $symbol, $totalSupply, $inviteReward, [$level1, $level2, $level3], $rewardPoolPercent]
            );
            $encodedParams = substr($encodedParams, 2);
            $fullBytecode = $bytecode . $encodedParams;

            $estimatedGas = $rpc->call('eth_estimateGas', [['from' => $admin, 'data' => $fullBytecode]]);
            $gasLimit = (int)(hexdec($estimatedGas) * 1.2);

            $txHash = $rpc->call('eth_sendTransaction', [[
                'from' => $admin,
                'data' => $fullBytecode,
                'gas' => '0x' . dechex($gasLimit),
                'gasPrice' => '0x' . dechex(1000000000)
            ]]);

            // 等待确认
            $receipt = null;
            for ($i = 0; $i < 60; $i++) {
                sleep(1);
                $receipt = $rpc->call('eth_getTransactionReceipt', [$txHash]);
                if ($receipt) break;
            }

            if (!$receipt || $receipt['status'] !== '0x1') {
                throw new \Exception(t('deploy_failed'));
            }

            $contractAddress = $receipt['contractAddress'];
            file_put_contents(contractAddressFile(), $contractAddress);

            return response()->json([
                'success' => true,
                'message' => t('deploy_success'),
                'contract_address' => $contractAddress,
                'tx_hash' => $txHash,
                'gas_used' => hexdec($receipt['gasUsed'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
