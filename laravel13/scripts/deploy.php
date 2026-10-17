<?php
/**
 * 部署邀请奖励代币合约到 Geth 节点
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\RpcService;
use Web3\Contracts\Ethabi;
use Web3\Utils;

$rpc = RpcService::getInstance();

// ============ RPC 调用封装 ============
function waitForReceipt($txHash, $timeout = 60) {
    global $rpc;
    for ($i = 0; $i < $timeout; $i++) {
        sleep(1);
        $receipt = $rpc->call('eth_getTransactionReceipt', [$txHash]);
        if ($receipt) {
            return $receipt;
        }
    }
    throw new Exception("等待交易回执超时");
}

// ============ 部署参数 ============
$totalSupply = 1000000;      // 100万代币
$inviteReward = 100;          // 每次邀请奖励 100 IRT
$referralLevels = [50, 30, 20]; // 三级推荐比例
$rewardPoolPercent = 30;      // 30% 作为奖励池

echo "=== 部署邀请奖励代币合约 ===\n\n";

// 获取管理员账户
$accounts = $rpc->call('eth_accounts');
$admin = $accounts[0];
echo "管理员账户: $admin\n";

// 检查余额
$balance = $rpc->call('eth_getBalance', [$admin, 'latest']);
echo "ETH 余额: " . (hexdec($balance) / 10**18) . " ETH\n\n";

// 读取编译产物
$bytecode = '0x' . file_get_contents(__DIR__ . '/../storage/contracts/compiled/InviteRewardToken.bin');
$abi = json_decode(file_get_contents(__DIR__ . '/../storage/contracts/compiled/InviteRewardToken.abi'), true);

echo "部署参数:\n";
echo "  总供应量: $totalSupply IRT\n";
echo "  邀请奖励: $inviteReward IRT\n";
echo "  推荐比例: " . implode(', ', $referralLevels) . "\n";
echo "  奖励池比例: $rewardPoolPercent%\n\n";

// 编码构造函数参数
$ethabi = new Ethabi();

// 构造函数签名: (uint256, uint256, uint256[3], uint256)
$encodedParams = $ethabi->encodeParameters(
    ['uint256', 'uint256', 'uint256[3]', 'uint256'],
    [$totalSupply, $inviteReward, $referralLevels, $rewardPoolPercent]
);
$encodedParams = substr($encodedParams, 2); // 去掉 0x

$fullBytecode = $bytecode . $encodedParams;

echo "字节码大小: " . strlen($fullBytecode) . " 字符\n";

// 估算 Gas
echo "\n估算 Gas...\n";
try {
    $estimatedGas = $rpc->call('eth_estimateGas', [['from' => $admin, 'data' => $fullBytecode]]);
    $gasLimit = (int)(hexdec($estimatedGas) * 1.2);
    echo "估算 Gas: " . hexdec($estimatedGas) . "\n";
    echo "实际使用: $gasLimit\n";
} catch (Exception $e) {
    $gasLimit = 2000000;
    echo "估算失败，使用默认值: $gasLimit\n";
}

// 发送部署交易
echo "\n发送部署交易...\n";
$txHash = $rpc->call('eth_sendTransaction', [[
    'from' => $admin,
    'data' => $fullBytecode,
    'gas' => '0x' . dechex($gasLimit),
    'gasPrice' => '0x' . dechex(1000000000) // 1 Gwei
]]);
echo "交易哈希: $txHash\n";

// 等待确认
echo "\n等待区块确认...\n";
$receipt = waitForReceipt($txHash);

$status = $receipt['status'] === '0x1' ? '成功' : '失败';
$gasUsed = hexdec($receipt['gasUsed']);
$contractAddress = $receipt['contractAddress'] ?? '';

echo "\n=== 部署结果 ===\n";
echo "状态: $status\n";
echo "Gas 使用: $gasUsed\n";
echo "合约地址: $contractAddress\n";

if ($receipt['status'] !== '0x1') {
    echo "\n部署失败！\n";
    exit(1);
}

// 验证合约
$code = $rpc->call('eth_getCode', [$contractAddress, 'latest']);
echo "合约字节码: " . (strlen($code) > 2 ? "已部署" : "未找到") . "\n";

// 保存合约地址
file_put_contents(__DIR__ . '/../storage/contracts/compiled/contract_address.txt', $contractAddress);

// 验证合约状态
echo "\n=== 验证合约状态 ===\n";

// 读取 totalSupply
$sig = substr(Utils::sha3('totalSupply()'), 0, 10);
$result = $rpc->call('eth_call', [['to' => $contractAddress, 'data' => $sig], 'latest']);
echo "总供应量: " . (hexdec($result) / 10**18) . " IRT\n";

// 读取 admin
$sig = substr(Utils::sha3('admin()'), 0, 10);
$result = $rpc->call('eth_call', [['to' => $contractAddress, 'data' => $sig], 'latest']);
echo "管理员: $result\n";

// 读取 inviteReward
$sig = substr(Utils::sha3('inviteReward()'), 0, 10);
$result = $rpc->call('eth_call', [['to' => $contractAddress, 'data' => $sig], 'latest']);
echo "邀请奖励: " . (hexdec($result) / 10**18) . " IRT\n";

// 读取 rewardPool
$sig = substr(Utils::sha3('rewardPool()'), 0, 10);
$result = $rpc->call('eth_call', [['to' => $contractAddress, 'data' => $sig], 'latest']);
echo "奖励池: " . (hexdec($result) / 10**18) . " IRT\n";

// 读取 balanceOf(admin)
$sig = substr(Utils::sha3('balanceOf(address)'), 0, 10);
$encoded = $ethabi->encodeParameters(['address'], [$admin]);
$result = $rpc->call('eth_call', [['to' => $contractAddress, 'data' => $sig . substr($encoded, 2)], 'latest']);
echo "管理员代币余额: " . (hexdec($result) / 10**18) . " IRT\n";

echo "\n部署完成! 合约地址已保存到 contract_address.txt\n";
