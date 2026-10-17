<?php
/**
 * 邀请奖励代币 - 交互脚本
 * 
 * 用法:
 *   php interact.php balance <地址>           查询代币余额
 *   php interact.php register <邀请人地址>    注册用户
 *   php interact.php invite-count <地址>      查询邀请人数
 *   php interact.php inviter <地址>           查询邀请人
 *   php interact.php pool                     查询奖励池信息
 *   php interact.php transfer <目标> <金额>   转账代币
 *   php interact.php rewards <地址>           查询累计奖励
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\RpcService;
use Web3\Contracts\Ethabi;
use Web3\Utils;

$rpc = RpcService::getInstance();

function callContract($contractAddress, $functionSig, $encodedParams = '') {
    global $rpc;
    $sig = substr(Utils::sha3($functionSig), 0, 10);
    $data = $sig . $encodedParams;
    return $rpc->call('eth_call', [['to' => $contractAddress, 'data' => $data], 'latest']);
}

function sendTx($from, $contractAddress, $functionSig, $encodedParams = '') {
    global $rpc;
    $sig = substr(Utils::sha3($functionSig), 0, 10);
    $data = $sig . $encodedParams;
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
            return $receipt['status'] === '0x1';
        }
    }
    return false;
}

// 加载合约
$contractAddress = @file_get_contents(__DIR__ . '/../storage/contracts/compiled/contract_address.txt');
if (!$contractAddress) {
    die("错误: 合约未部署，请先运行 deploy.php\n");
}
$ethabi = new Ethabi();

$command = $argv[1] ?? 'help';
$arg1 = $argv[2] ?? '';
$arg2 = $argv[3] ?? '';

switch ($command) {
    case 'balance':
        $addr = $arg1 ?: $rpc->call('eth_accounts')[0];
        $encoded = substr($ethabi->encodeParameters(['address'], [$addr]), 2);
        $result = callContract($contractAddress, 'balanceOf(address)', $encoded);
        $balance = hexdec($result) / 10**18;
        echo "余额: $balance IRT ($addr)\n";
        break;

    case 'register':
        $inviter = $arg1;
        if (!$inviter) die("用法: php interact.php register <邀请人地址>\n");
        $accounts = $rpc->call('eth_accounts');
        $from = $accounts[0];
        $encoded = substr($ethabi->encodeParameters(['address'], [$inviter]), 2);
        $ok = sendTx($from, $contractAddress, 'register(address)', $encoded);
        echo $ok ? "注册成功!\n" : "注册失败!\n";
        break;

    case 'invite-count':
        $addr = $arg1 ?: $rpc->call('eth_accounts')[0];
        $encoded = substr($ethabi->encodeParameters(['address'], [$addr]), 2);
        $result = callContract($contractAddress, 'inviteCount(address)', $encoded);
        echo "邀请人数: " . hexdec($result) . " ($addr)\n";
        break;

    case 'inviter':
        $addr = $arg1 ?: $rpc->call('eth_accounts')[0];
        $encoded = substr($ethabi->encodeParameters(['address'], [$addr]), 2);
        $result = callContract($contractAddress, 'inviterOf(address)', $encoded);
        echo "邀请人: $result ($addr)\n";
        break;

    case 'rewards':
        $addr = $arg1 ?: $rpc->call('eth_accounts')[0];
        $encoded = substr($ethabi->encodeParameters(['address'], [$addr]), 2);
        $result = callContract($contractAddress, 'totalRewardsEarned(address)', $encoded);
        echo "累计奖励: " . (hexdec($result) / 10**18) . " IRT ($addr)\n";
        break;

    case 'pool':
        $result = callContract($contractAddress, 'rewardPool()');
        echo "奖励池余额: " . (hexdec($result) / 10**18) . " IRT\n";

        $result = callContract($contractAddress, 'inviteReward()');
        echo "每次邀请奖励: " . (hexdec($result) / 10**18) . " IRT\n";

        $result = callContract($contractAddress, 'rewardPool()');
        $pool = hexdec($result);
        $rewardResult = callContract($contractAddress, 'inviteReward()');
        $reward = hexdec($rewardResult);
        echo "剩余可邀请次数: " . ($reward > 0 ? intdiv($pool, $reward) : 0) . "\n";
        break;

    case 'transfer':
        $to = $arg1;
        $amount = $arg2;
        if (!$to || !$amount) die("用法: php interact.php transfer <目标地址> <金额>\n");
        $accounts = $rpc->call('eth_accounts');
        $from = $accounts[0];
        $amountWei = $amount * 10**18;
        $encoded = substr($ethabi->encodeParameters(['address', 'uint256'], [$to, $amountWei]), 2);
        $ok = sendTx($from, $contractAddress, 'transfer(address,uint256)', $encoded);
        echo $ok ? "转账成功: $amount IRT -> $to\n" : "转账失败!\n";
        break;

    case 'help':
    default:
        echo "邀请奖励代币 - 交互工具\n\n";
        echo "命令:\n";
        echo "  balance [地址]            查询代币余额\n";
        echo "  register <邀请人地址>     注册用户\n";
        echo "  invite-count [地址]       查询邀请人数\n";
        echo "  inviter [地址]            查询邀请人\n";
        echo "  rewards [地址]            查询累计奖励\n";
        echo "  pool                      查询奖励池信息\n";
        echo "  transfer <目标> <金额>    转账代币\n";
        break;
}
