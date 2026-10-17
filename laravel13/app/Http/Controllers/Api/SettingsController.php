<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RpcService;

class SettingsController extends Controller
{
    public function index()
    {
        $rpc = RpcService::getInstance();

        try {
            if (!request()->isMethod('POST')) {
                throw new \Exception(t('only_post_allowed'));
            }

            $contractAddress = readContractAddress();
            if (!$contractAddress) {
                throw new \Exception(t('contract_not_deployed'));
            }

            $inviteReward = intval(request()->post('invite_reward', 0));
            $level1 = intval(request()->post('level1', 0));
            $level2 = intval(request()->post('level2', 0));
            $level3 = intval(request()->post('level3', 0));

            if ($inviteReward <= 0) throw new \Exception(t('invite_reward_must_positive'));
            if ($level1 + $level2 + $level3 > 100) throw new \Exception(t('ratio_sum_error'));

            $accounts = $rpc->call('eth_accounts');
            $admin = $accounts[0];

            // 更新邀请奖励
            $inviteRewardData = encodeCall('setInviteReward', ['uint256'], [$inviteReward]);
            $inviteRewardResult = $this->sendTx($admin, $contractAddress, $inviteRewardData);
            if (!$inviteRewardResult['success']) {
                throw new \Exception(t('update_invite_reward_failed'));
            }

            // 更新推荐比例
            $referralLevelsData = encodeCall('setReferralLevels', ['uint256[3]'], [[$level1, $level2, $level3]]);
            $referralLevelsResult = $this->sendTx($admin, $contractAddress, $referralLevelsData);
            if (!$referralLevelsResult['success']) {
                throw new \Exception(t('update_referral_levels_failed'));
            }

            return response()->json([
                'success' => true,
                'message' => t('settings_saved'),
                'invite_reward' => $inviteReward,
                'referral_levels' => [$level1, $level2, $level3]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function sendTx($from, $contractAddress, $data)
    {
        $rpc = RpcService::getInstance();
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
}
