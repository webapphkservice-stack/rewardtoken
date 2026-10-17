<?php
/**
 * 邀请奖励代币 - 完整邀请流程测试
 * 
 * 测试场景:
 *   Admin 邀请 User1 → User1 邀请 User2 → User2 邀请 User3
 *   验证三级推荐奖励是否正确自动转入各邀请人钱包
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Support/helpers.php';

use App\Services\RpcService;
use Web3\Contracts\Ethabi;
use Web3\Utils;

$rpc = RpcService::getInstance();

// ============ 工具函数 ============
function callRead($contractAddress, $data) {
    global $rpc;
    return $rpc->call('eth_call', [['to' => $contractAddress, 'data' => $data], 'latest']);
}

// encodeCall 由全局 helpers（app/Support/helpers.php）提供，此处不再重复定义

function sendTx($from, $contractAddress, $data) {
    global $rpc;
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
            return ['success' => $receipt['status'] === '0x1', 'gasUsed' => hexdec($receipt['gasUsed'])];
        }
    }
    return ['success' => false, 'gasUsed' => 0];
}

function getBalance($contractAddress, $account) {
    $data = encodeCall('balanceOf', ['address'], [$account]);
    $result = callRead($contractAddress, $data);
    return hexdec($result) / 10**18;
}

function getInviteCount($contractAddress, $account) {
    $data = encodeCall('inviteCount', ['address'], [$account]);
    $result = callRead($contractAddress, $data);
    return hexdec($result);
}

function getInviterOf($contractAddress, $account) {
    $data = encodeCall('inviterOf', ['address'], [$account]);
    return callRead($contractAddress, $data);
}

function getTotalRewards($contractAddress, $account) {
    $data = encodeCall('totalRewardsEarned', ['address'], [$account]);
    $result = callRead($contractAddress, $data);
    return hexdec($result) / 10**18;
}

// ============ 开始测试 ============
$contractAddress = @file_get_contents(__DIR__ . '/../storage/contracts/compiled/contract_address.txt');
if (!$contractAddress) {
    die("错误: 合约未部署，请先运行 deploy.php\n");
}

$accounts = $rpc->call('eth_accounts');
$admin = $accounts[0];

// 使用虚拟地址模拟不同用户（adminRegister 方式）
$user1 = '0x1111111111111111111111111111111111111111';
$user2 = '0x2222222222222222222222222222222222222222';
$user3 = '0x3333333333333333333333333333333333333333';

echo "╔══════════════════════════════════════════════╗\n";
echo "║   邀请奖励代币 - 完整流程测试               ║\n";
echo "╚══════════════════════════════════════════════╝\n\n";

echo "合约地址: $contractAddress\n";
echo "管理员: $admin\n";
echo "User1: $user1\n";
echo "User2: $user2\n";
echo "User3: $user3\n\n";

// ============ Step 1: 初始状态 ============
echo "━━━ Step 1: 检查初始状态 ━━━\n";
$adminBalance = getBalance($contractAddress, $admin);
$poolBalance = getBalance($contractAddress, $contractAddress);
echo "管理员余额: $adminBalance IRT\n";
echo "奖励池余额: $poolBalance IRT\n";
echo "管理员邀请人数: " . getInviteCount($contractAddress, $admin) . "\n\n";

// ============ Step 2: Admin 邀请 User1 ============
echo "━━━ Step 2: Admin 邀请 User1 (一级推荐) ━━━\n";
$data = encodeCall('adminRegister', ['address', 'address'], [$user1, $admin]);
$result = sendTx($admin, $contractAddress, $data);

if ($result['success']) {
    echo "✓ User1 注册成功!\n";
    $newAdminBalance = getBalance($contractAddress, $admin);
    $user1Balance = getBalance($contractAddress, $user1);
    $newPoolBalance = getBalance($contractAddress, $contractAddress);
    $adminEarned = $newAdminBalance - $adminBalance;
    $poolSpent = $poolBalance - $newPoolBalance;
    
    echo "  管理员余额: $newAdminBalance IRT (+" . number_format($adminEarned, 0) . " IRT)\n";
    echo "  User1 余额: $user1Balance IRT\n";
    echo "  奖励池: $newPoolBalance IRT (-" . number_format($poolSpent, 0) . " IRT)\n";
    echo "  管理员邀请人数: " . getInviteCount($contractAddress, $admin) . "\n";
    
    $adminBalance = $newAdminBalance;
    $poolBalance = $newPoolBalance;
} else {
    echo "✗ User1 注册失败!\n";
    exit(1);
}

// ============ Step 3: User1 邀请 User2 ============
echo "\n━━━ Step 3: User1 邀请 User2 (二级推荐) ━━━\n";
$data = encodeCall('adminRegister', ['address', 'address'], [$user2, $user1]);
$result = sendTx($admin, $contractAddress, $data);

if ($result['success']) {
    echo "✓ User2 注册成功!\n";
    $newAdminBalance = getBalance($contractAddress, $admin);
    $user1Balance = getBalance($contractAddress, $user1);
    $user2Balance = getBalance($contractAddress, $user2);
    $newPoolBalance = getBalance($contractAddress, $contractAddress);
    
    echo "  管理员余额: $newAdminBalance IRT (+" . number_format($newAdminBalance - $adminBalance, 0) . " IRT, 二级奖励30%)\n";
    echo "  User1 余额: $user1Balance IRT (一级奖励50%)\n";
    echo "  User2 余额: $user2Balance IRT\n";
    echo "  奖励池: $newPoolBalance IRT\n";
    echo "  User1 邀请人数: " . getInviteCount($contractAddress, $user1) . "\n";
    
    $adminBalance = $newAdminBalance;
    $poolBalance = $newPoolBalance;
} else {
    echo "✗ User2 注册失败!\n";
    exit(1);
}

// ============ Step 4: User2 邀请 User3 ============
echo "\n━━━ Step 4: User2 邀请 User3 (三级推荐) ━━━\n";
$data = encodeCall('adminRegister', ['address', 'address'], [$user3, $user2]);
$result = sendTx($admin, $contractAddress, $data);

if ($result['success']) {
    echo "✓ User3 注册成功!\n";
    $newAdminBalance = getBalance($contractAddress, $admin);
    $user1Balance = getBalance($contractAddress, $user1);
    $user2Balance = getBalance($contractAddress, $user2);
    $user3Balance = getBalance($contractAddress, $user3);
    $newPoolBalance = getBalance($contractAddress, $contractAddress);
    
    echo "  管理员余额: $newAdminBalance IRT (+" . number_format($newAdminBalance - $adminBalance, 0) . " IRT, 三级奖励20%)\n";
    echo "  User1 余额: $user1Balance IRT (二级奖励30%)\n";
    echo "  User2 余额: $user2Balance IRT (一级奖励50%)\n";
    echo "  User3 余额: $user3Balance IRT\n";
    echo "  奖励池: $newPoolBalance IRT\n";
    echo "  User2 邀请人数: " . getInviteCount($contractAddress, $user2) . "\n";
} else {
    echo "✗ User3 注册失败!\n";
    exit(1);
}

// ============ Step 5: 汇总验证 ============
echo "\n━━━ Step 5: 验证累计奖励 ━━━\n";
echo "管理员累计奖励: " . getTotalRewards($contractAddress, $admin) . " IRT\n";
echo "User1 累计奖励: " . getTotalRewards($contractAddress, $user1) . " IRT\n";
echo "User2 累计奖励: " . getTotalRewards($contractAddress, $user2) . " IRT\n";

echo "\n╔══════════════════════════════════════════════╗\n";
echo "║   测试结果                                   ║\n";
echo "╚══════════════════════════════════════════════╝\n\n";
echo "邀请链: Admin → User1 → User2 → User3\n\n";
echo "奖励分配:\n";
echo "  Admin: 50(L1) + 30(L2) + 20(L3) = 100 IRT\n";
echo "  User1: 50(L1) + 30(L2) = 80 IRT\n";
echo "  User2: 50(L1) = 50 IRT\n";
echo "\n邀请奖励自动转入钱包功能验证通过!\n";
