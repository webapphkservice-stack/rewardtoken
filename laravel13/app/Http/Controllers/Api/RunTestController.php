<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RpcService;
use Web3\Contracts\Ethabi;
use Web3\Utils;

class RunTestController extends Controller
{
    /**
     * 邀请奖励代币 - 完整邀请流程测试 (Web API)
     */
    public function index()
    {
        $rpc = RpcService::getInstance();

        try {
            $contractAddress = readContractAddress();
            if (!$contractAddress) {
                throw new \Exception(t('contract_not_deployed_run_test'));
            }

            $accounts = $rpc->call('eth_accounts');
            $admin = $accounts[0];

            // 使用随机虚拟地址模拟不同用户（避免重复注册）
            $user1 = '0x' . bin2hex(random_bytes(20));
            $user2 = '0x' . bin2hex(random_bytes(20));
            $user3 = '0x' . bin2hex(random_bytes(20));

            $steps = [];
            $rewards = [];

            // Step 1: 初始状态
            $adminBalance = $this->getBalance($rpc, $contractAddress, $admin);
            $poolBalance = $this->getBalance($rpc, $contractAddress, $contractAddress);
            $steps[] = [
                'title' => t('check_initial_state'),
                'detail' => t('admin_balance') . ": " . number_format($adminBalance, 0) . " IRT\n" . t('reward_pool_balance') . ": " . number_format($poolBalance, 0) . " IRT\n" . t('admin_invite_count') . ": " . $this->getInviteCount($rpc, $contractAddress, $admin)
            ];

            // Step 2: Admin 邀请 User1
            $data = encodeCall('adminRegister', ['address', 'address'], [$user1, $admin]);
            $result = $this->sendTx($rpc, $admin, $contractAddress, $data);
            if (!$result['success']) {
                throw new \Exception(t('user1_register_failed'));
            }
            $newAdminBalance = $this->getBalance($rpc, $contractAddress, $admin);
            $user1Balance = $this->getBalance($rpc, $contractAddress, $user1);
            $newPoolBalance = $this->getBalance($rpc, $contractAddress, $contractAddress);
            $adminEarned = $newAdminBalance - $adminBalance;
            $poolSpent = $poolBalance - $newPoolBalance;
            $steps[] = [
                'title' => t('admin_invite_user1_level1'),
                'detail' => t('user1_register_success') . "\n" . t('admin_balance') . ": " . number_format($newAdminBalance, 0) . " IRT (+" . number_format($adminEarned, 0) . " IRT)\n" . t('user1_balance') . ": " . number_format($user1Balance, 0) . " IRT\n" . t('reward_pool') . ": " . number_format($newPoolBalance, 0) . " IRT (-" . number_format($poolSpent, 0) . " IRT)\n" . t('admin_invite_count') . ": " . $this->getInviteCount($rpc, $contractAddress, $admin)
            ];
            $adminBalance = $newAdminBalance;
            $poolBalance = $newPoolBalance;

            // Step 3: User1 邀请 User2
            $data = encodeCall('adminRegister', ['address', 'address'], [$user2, $user1]);
            $result = $this->sendTx($rpc, $admin, $contractAddress, $data);
            if (!$result['success']) {
                throw new \Exception(t('user2_register_failed'));
            }
            $newAdminBalance = $this->getBalance($rpc, $contractAddress, $admin);
            $user1Balance = $this->getBalance($rpc, $contractAddress, $user1);
            $user2Balance = $this->getBalance($rpc, $contractAddress, $user2);
            $newPoolBalance = $this->getBalance($rpc, $contractAddress, $contractAddress);
            $steps[] = [
                'title' => t('user1_invite_user2_level2'),
                'detail' => t('user2_register_success') . "\n" . t('admin_balance') . ": " . number_format($newAdminBalance, 0) . " IRT (+" . number_format($newAdminBalance - $adminBalance, 0) . " IRT, " . t('level2_reward_30') . ")\n" . t('user1_balance') . ": " . number_format($user1Balance, 0) . " IRT (" . t('level1_reward_50') . ")\n" . t('user2_balance') . ": " . number_format($user2Balance, 0) . " IRT\n" . t('reward_pool') . ": " . number_format($newPoolBalance, 0) . " IRT\n" . t('user1_invite_count') . ": " . $this->getInviteCount($rpc, $contractAddress, $user1)
            ];
            $adminBalance = $newAdminBalance;
            $poolBalance = $newPoolBalance;

            // Step 4: User2 邀请 User3
            $data = encodeCall('adminRegister', ['address', 'address'], [$user3, $user2]);
            $result = $this->sendTx($rpc, $admin, $contractAddress, $data);
            if (!$result['success']) {
                throw new \Exception(t('user3_register_failed'));
            }
            $newAdminBalance = $this->getBalance($rpc, $contractAddress, $admin);
            $user1Balance = $this->getBalance($rpc, $contractAddress, $user1);
            $user2Balance = $this->getBalance($rpc, $contractAddress, $user2);
            $user3Balance = $this->getBalance($rpc, $contractAddress, $user3);
            $newPoolBalance = $this->getBalance($rpc, $contractAddress, $contractAddress);
            $steps[] = [
                'title' => t('user2_invite_user3_level3'),
                'detail' => t('user3_register_success') . "\n" . t('admin_balance') . ": " . number_format($newAdminBalance, 0) . " IRT (+" . number_format($newAdminBalance - $adminBalance, 0) . " IRT, " . t('level3_reward_20') . ")\n" . t('user1_balance') . ": " . number_format($user1Balance, 0) . " IRT (" . t('level2_reward_30') . ")\n" . t('user2_balance') . ": " . number_format($user2Balance, 0) . " IRT (" . t('level1_reward_50') . ")\n" . t('user3_balance') . ": " . number_format($user3Balance, 0) . " IRT\n" . t('reward_pool') . ": " . number_format($newPoolBalance, 0) . " IRT\n" . t('user2_invite_count') . ": " . $this->getInviteCount($rpc, $contractAddress, $user2)
            ];

            // Step 5: 汇总
            $steps[] = [
                'title' => t('verify_total_rewards'),
                'detail' => t('admin_total_reward') . ": " . number_format($this->getTotalRewards($rpc, $contractAddress, $admin), 0) . " IRT\n" . t('user1_total_reward') . ": " . number_format($this->getTotalRewards($rpc, $contractAddress, $user1), 0) . " IRT\n" . t('user2_total_reward') . ": " . number_format($this->getTotalRewards($rpc, $contractAddress, $user2), 0) . " IRT"
            ];

            $rewards = [
                ['user' => t('admin'), 'amount' => number_format($this->getTotalRewards($rpc, $contractAddress, $admin), 0)],
                ['user' => t('user1'), 'amount' => number_format($this->getTotalRewards($rpc, $contractAddress, $user1), 0)],
                ['user' => t('user2'), 'amount' => number_format($this->getTotalRewards($rpc, $contractAddress, $user2), 0)],
            ];

            return response()->json([
                'success' => true,
                'steps' => $steps,
                'rewards' => $rewards
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function callRead(RpcService $rpc, string $contractAddress, string $data)
    {
        return $rpc->call('eth_call', [['to' => $contractAddress, 'data' => $data], 'latest']);
    }

    private function sendTx(RpcService $rpc, $from, string $contractAddress, string $data)
    {
        $txHash = $rpc->call('eth_sendTransaction', [[
            'from' => $from,
            'to' => $contractAddress,
            'data' => $data,
            'gas' => '0x' . dechex(2000000),
            'gasPrice' => '0x' . dechex(1000000000)
        ]]);

        for ($i = 0; $i < 30; $i++) {
            sleep(1);
            $receipt = $rpc->call('eth_getTransactionReceipt', [$txHash]);
            if ($receipt) {
                return ['success' => $receipt['status'] === '0x1', 'gasUsed' => hexdec($receipt['gasUsed']), 'txHash' => $txHash];
            }
        }
        return ['success' => false, 'gasUsed' => 0, 'txHash' => $txHash];
    }

    private function getBalance(RpcService $rpc, string $contractAddress, string $account)
    {
        $data = encodeCall('balanceOf', ['address'], [$account]);
        $result = $this->callRead($rpc, $contractAddress, $data);
        return hexdec($result) / 10**18;
    }

    private function getInviteCount(RpcService $rpc, string $contractAddress, string $account)
    {
        $data = encodeCall('inviteCount', ['address'], [$account]);
        $result = $this->callRead($rpc, $contractAddress, $data);
        return hexdec($result);
    }

    private function getTotalRewards(RpcService $rpc, string $contractAddress, string $account)
    {
        $data = encodeCall('totalRewardsEarned', ['address'], [$account]);
        $result = $this->callRead($rpc, $contractAddress, $data);
        return hexdec($result) / 10**18;
    }
}
