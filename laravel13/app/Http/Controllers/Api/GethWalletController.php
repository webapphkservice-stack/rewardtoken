<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RpcService;
use Web3\Utils;
use Web3p\EthereumTx\Transaction;

class GethWalletController extends Controller
{
    // geth --dev 模式下第 0 个账户默认解锁并持币，用于给新账户自动注资 gas
    private const DEV_FUNDER = '0x71562b71999873db5b286df957af199ec94617f7';
    // 单次注资额度（wei）= 1 ETH，足够覆盖注册/转账 gas 费
    private const FUND_AMOUNT_WEI = '1000000000000000000';

    public function index()
    {
        $rpc = RpcService::getInstance();

        try {
            $action = trim(request()->input('action', ''));

            switch ($action) {
                case 'list':
                    $accounts = $rpc->call('eth_accounts');
                    if (empty($accounts)) {
                        jsonResponse(true, t('no_accounts'), []);
                    } else {
                        jsonResponse(true, '', $accounts);
                    }
                    break;

                case 'ensure_wallet':
                    if (!request()->isMethod('POST')) {
                        throw new \Exception(t('only_post_allowed'));
                    }
                    // 进入钱包注册页：自动创建钱包，私钥仅本次返回供用户保存，项目不保存私钥明文。
                    $password = gethMasterPassword(); // 统一可复现密码，便于后续离线签名（私钥本体不落库）
                    $passFile = tempnam(sys_get_temp_dir(), 'gethpass');
                    file_put_contents($passFile, $password);
                    $output = [];
                    exec('geth account new --datadir "' . storage_path('geth') . '" --password "' . $passFile . '" 2>&1', $output, $rc);
                    @unlink($passFile);
                    $address = null;
                    foreach ($output as $line) {
                        if (preg_match('/(0x[0-9a-fA-F]{40})/', $line, $m)) {
                            $address = strtolower($m[1]);
                            break;
                        }
                    }
                    if ($rc !== 0 || !$address) {
                        throw new \Exception(t('create_account_failed'));
                    }
                    $privateKey = null;
                    $keyDir = storage_path('geth/keystore');
                    foreach (glob($keyDir . '/UTC--*--' . substr($address, 2)) ?: [] as $file) {
                        $ks = json_decode((string) file_get_contents($file), true);
                        if (is_array($ks)) {
                            $privateKey = decryptKeystoreV3($ks, $password);
                            if ($privateKey) {
                                break;
                            }
                        }
                    }
                    jsonResponse(true, t('create_account_success'), [
                        'address' => $address,
                        'private_key' => $privateKey, // 仅此一次返回，项目不落库
                    ]);
                    break;

                case 'create':
                    if (!request()->isMethod('POST')) {
                        throw new \Exception(t('only_post_allowed'));
                    }
                    $password = gethMasterPassword(); // keystore 统一密码，便于后续离线签名
                    if (empty(request()->post('password', ''))) {
                        throw new \Exception(t('password_required'));
                    }
                    // geth 1.17 移除了 personal namespace，改走 CLI 创建账户
                    $passFile = tempnam(sys_get_temp_dir(), 'gethpass');
                    file_put_contents($passFile, $password);
                    $output = [];
                    exec('geth account new --datadir "' . storage_path('geth') . '" --password "' . $passFile . '" 2>&1', $output, $rc);
                    @unlink($passFile);
                    $address = null;
                    foreach ($output as $line) {
                        if (preg_match('/(0x[0-9a-fA-F]{40})/', $line, $m)) {
                            $address = strtolower($m[1]);
                            break;
                        }
                    }
                    if ($rc !== 0 || !$address) {
                        throw new \Exception(t('create_account_failed'));
                    }
                    // 读取 keystore 并解密，返回私钥供用户保存
                    $privateKey = null;
                    // keystore 文件名使用不含 0x 前缀的裸地址
                    $keyDir = storage_path('geth/keystore');
                    foreach (glob($keyDir . '/UTC--*--' . substr($address, 2)) ?: [] as $file) {
                        $ks = json_decode((string) file_get_contents($file), true);
                        if (is_array($ks)) {
                            $privateKey = decryptKeystoreV3($ks, $password);
                            if ($privateKey) {
                                break;
                            }
                        }
                    }
                    jsonResponse(true, t('create_account_success'), [
                        'address' => $address,
                        'private_key' => $privateKey,
                    ]);
                    break;

                case 'unlock':
                    // geth 1.17 dev 模式账户默认解锁（personal namespace 已移除）
                    if (!request()->isMethod('POST')) {
                        throw new \Exception(t('only_post_allowed'));
                    }
                    $address = trim(request()->post('address', ''));
                    $password = trim(request()->post('password', ''));
                    if (!$address || !$password) {
                        throw new \Exception(t('address_password_required'));
                    }
                    jsonResponse(true, t('account_unlocked'), ['unlocked' => true]);
                    break;

                case 'balance':
                    $address = trim(request()->input('address', ''));
                    if (!$address) {
                        throw new \Exception(t('missing_required_fields'));
                    }
                    $contractAddress = readContractAddress();
                    if (!$contractAddress) {
                        throw new \Exception(t('contract_not_deployed'));
                    }

                    $data = encodeCall('balanceOf', ['address'], [$address]);
                    $rawBalance = $rpc->call('eth_call', [[
                        'to' => $contractAddress,
                        'data' => $data
                    ], 'latest']);

                    // 将 raw(wei 十六进制) 转为可读的 token 数量字符串（最多 8 位小数）
                    $wei = $rawBalance ? (string) Utils::toBn($rawBalance)->toString() : '0';
                    $balance = $this->weiToToken($wei);
                    jsonResponse(true, '', ['balance' => $balance, 'raw' => $rawBalance]);
                    break;

                case 'info':
                    $address = strtolower(trim(request()->input('address', '')));
                    if (!$address) {
                        throw new \Exception(t('missing_required_fields'));
                    }
                    $contractAddress = readContractAddress();
                    if (!$contractAddress) {
                        throw new \Exception(t('contract_not_deployed'));
                    }
                    $viewCall = function (string $fn, array $types, array $params) use ($rpc, $contractAddress) {
                        return $rpc->call('eth_call', [[
                            'to' => $contractAddress,
                            'data' => encodeCall($fn, $types, $params),
                        ], 'latest']);
                    };

                    $isRegRaw = $viewCall('isRegistered', ['address'], [$address]);
                    $isRegistered = $isRegRaw ? (strtolower(substr($isRegRaw, -2)) === '01') : false;
                    $inviterOf = $viewCall('inviterOf', ['address'], [$address]);
                    $inviteCount = $viewCall('inviteCount', ['address'], [$address]);
                    $totalRewards = $viewCall('totalRewardsEarned', ['address'], [$address]);
                    $chain = $viewCall('getInviterChain', ['address'], [$address]);
                    $balanceRaw = $viewCall('balanceOf', ['address'], [$address]);

                    $inviter = ($inviterOf && substr($inviterOf, -40) !== str_repeat('0', 40))
                        ? '0x' . strtolower(substr($inviterOf, -40))
                        : null;
                    $chainArr = [null, null, null];
                    if ($chain) {
                        $chainHex = substr($chain, 2);
                        for ($i = 0; $i < 3; $i++) {
                            $addrHex = substr($chainHex, $i * 64 + 24, 40);
                            $chainArr[$i] = $addrHex === str_repeat('0', 40) ? null : '0x' . strtolower($addrHex);
                        }
                    }

                    $balance = $balanceRaw ? $this->weiToToken((string) Utils::toBn($balanceRaw)->toString()) : '0';
                    $totalToken = $totalRewards ? $this->weiToToken((string) Utils::toBn($totalRewards)->toString()) : '0';

                    jsonResponse(true, '', [
                        'address' => $address,
                        'is_registered' => $isRegistered,
                        'inviter' => $inviter,
                        'inviter_chain' => $chainArr,
                        'invite_count' => $inviteCount ? (int) Utils::toBn($inviteCount)->toString() : 0,
                        'total_rewards' => $totalToken,
                        'balance' => $balance,
                    ]);
                    break;

                case 'txs':
                    $address = strtolower(trim(request()->input('address', '')));
                    if (!$address) {
                        throw new \Exception(t('missing_required_fields'));
                    }
                    $contractAddress = readContractAddress();
                    if (!$contractAddress) {
                        throw new \Exception(t('contract_not_deployed'));
                    }

                    $latest = $rpc->call('eth_blockNumber');
                    $filter = [
                        'address' => $contractAddress,
                        'fromBlock' => '0x0',
                        'toBlock' => $latest ?? 'latest',
                    ];

                    $transferTopic = Utils::sha3('Transfer(address,address,uint256)');
                    $registerTopic = Utils::sha3('UserRegistered(address,address)');
                    $rewardTopic = Utils::sha3('RewardDistributed(address,uint256,uint256)');

                    $addressOf = function ($topic) {
                        return '0x' . strtolower(substr($topic, 26));
                    };

                    $records = [];

                    // Transfer(from, to, value)
                    $logs = $rpc->call('eth_getLogs', [array_merge($filter, ['topics' => [$transferTopic]])]);
                    foreach ((array) $logs as $log) {
                        $from = $addressOf($log['topics'][1]);
                        $to = $addressOf($log['topics'][2]);
                        if (!in_array($address, [$from, $to], true)) {
                            continue;
                        }
                        $valueRaw = str_pad(substr($log['data'], 2, 64), 64, '0', STR_PAD_LEFT);
                        $records[] = [
                            'type' => 'transfer',
                            'from' => $from,
                            'to' => $to,
                            'value' => $this->weiToToken((string) Utils::toBn('0x' . $valueRaw)->toString()),
                            'block_number' => hexdec($log['blockNumber']),
                            'tx_hash' => $log['transactionHash'],
                        ];
                    }

                    // UserRegistered(user, inviter)
                    $logs = $rpc->call('eth_getLogs', [array_merge($filter, ['topics' => [$registerTopic]])]);
                    foreach ((array) $logs as $log) {
                        $user = $addressOf($log['topics'][1]);
                        $inviter = $addressOf($log['topics'][2]);
                        if (!in_array($address, [$user, $inviter], true)) {
                            continue;
                        }
                        $records[] = [
                            'type' => 'registered',
                            'user' => $user,
                            'inviter' => $inviter,
                            'block_number' => hexdec($log['blockNumber']),
                            'tx_hash' => $log['transactionHash'],
                        ];
                    }

                    // RewardDistributed(inviter, amount, level)
                    $logs = $rpc->call('eth_getLogs', [array_merge($filter, ['topics' => [$rewardTopic]])]);
                    foreach ((array) $logs as $log) {
                        $inviter = $addressOf($log['topics'][1]);
                        if ($inviter !== $address) {
                            continue;
                        }
                        $dataHex = substr($log['data'], 2);
                        $level = hexdec(substr($dataHex, 64, 64));
                        $amountRaw = substr($dataHex, 0, 64);
                        $records[] = [
                            'type' => 'reward',
                            'inviter' => $inviter,
                            'level' => $level,
                            'value' => $this->weiToToken((string) Utils::toBn('0x' . $amountRaw)->toString()),
                            'block_number' => hexdec($log['blockNumber']),
                            'tx_hash' => $log['transactionHash'],
                        ];
                    }

                    usort($records, function ($a, $b) {
                        return $b['block_number'] - $a['block_number'];
                    });

                    jsonResponse(true, '', array_slice($records, 0, 100));
                    break;

                case 'register':
                    if (!request()->isMethod('POST')) {
                        throw new \Exception(t('only_post_allowed'));
                    }
                    $from = trim(request()->post('from', ''));
                    $inviter = trim(request()->post('inviter', ''));
                    $password = trim(request()->post('password', ''));
                    $username = trim(request()->post('username', ''));

                    if (!$from || !$password) {
                        throw new \Exception(t('missing_required_fields'));
                    }
                    if ($username === '') {
                        throw new \Exception(t('username_required'));
                    }
                    if (!$inviter) {
                        $inviter = '0x0000000000000000000000000000000000000000';
                    }

                    $contractAddress = readContractAddress();
                    if (!$contractAddress) {
                        throw new \Exception(t('contract_not_deployed'));
                    }

                    $data = encodeCall('register', ['address'], [$inviter]);
                    $txHash = $this->signAndSendRaw($from, $contractAddress, $data, 2000000);

                    // 记录账户密码(用于登录，仅存哈希，不存私钥)
                    $accountsFile = storage_path('accounts.json');
                    $accounts = is_file($accountsFile) ? (json_decode((string) file_get_contents($accountsFile), true) ?: []) : [];
                    foreach ($accounts as $acc) {
                        if (isset($acc['username']) && $acc['username'] === $username) {
                            throw new \Exception(t('username_taken'));
                        }
                    }
                    try {
                        $accounts[$from] = [
                            'username' => $username,
                            'password' => password_hash($password, PASSWORD_BCRYPT),
                            'inviter'  => $inviter,
                            'registered_at' => time(),
                        ];
                        file_put_contents($accountsFile, json_encode($accounts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    } catch (\Throwable $e) {
                        // 密码记录失败不影响注册主流程
                    }
                    jsonResponse(true, t('register_success'), ['tx_hash' => $txHash]);
                    break;

                case 'send':
                    if (!request()->isMethod('POST')) {
                        throw new \Exception(t('only_post_allowed'));
                    }
                    $from = trim(request()->post('from', ''));
                    $to = trim(request()->post('to', ''));
                    $amount = trim(request()->post('amount', ''));
                    $password = trim(request()->post('password', ''));

                    if (!$from || !$to || !$amount || !$password) {
                        throw new \Exception(t('missing_required_fields'));
                    }

                    $contractAddress = readContractAddress();
                    if (!$contractAddress) {
                        throw new \Exception(t('contract_not_deployed'));
                    }

                    // 转账手续费配置（百分比，0 表示免费）
                    $config = getSiteConfig();
                    $feePercent = is_numeric($config['transfer_fee'] ?? null) ? (float) $config['transfer_fee'] : 0;
                    if ($feePercent < 0) {
                        $feePercent = 0;
                    } elseif ($feePercent > 100) {
                        $feePercent = 100;
                    }

                    // 换算 18 位小数原始值（十进制字符串）。send 入参 amount 为用户打算转给收款人的净额。
                    $rawNet = Utils::toBn(Utils::toWei($amount, 'ether'))->toString();
                    if ($rawNet && bccomp($rawNet, '0') < 0) {
                        $rawNet = null;
                    }

                    if ($feePercent > 0 && $rawNet && bccomp($rawNet, '0') > 0) {
                        // fee = net * feePercent / 100（向上取整到整数 wei）
                        $feeStr = bcmul($rawNet, (string) ((int) round($feePercent * 100)), 0);
                        $feeStr = bcdiv($feeStr, '10000', 18); // 2 位分子精度：×feePercent% 化为 ×(feePercent*100)/10000
                        // 向上取整：若含小数则进 1
                        if (strpos($feeStr, '.') !== false) {
                            [$whole, $frac] = explode('.', $feeStr, 2);
                            $feeStr = trim($frac, '0') === '' ? $whole : (string) (intval($whole) + 1);
                        }
                        $rpc = RpcService::getInstance();
                        $feeHex = '0x' . ltrim(gmp_strval(gmp_init($feeStr, 10), 16), '0');
                        if ($feeHex === '0x') {
                            $feeHex = '0x0';
                        }
                        // 手续费进 admin 账户
                        $admin = $this->contractAdmin($rpc);
                        if (!$admin) {
                            throw new \Exception(t('action_failed'));
                        }
                        $feeTx = $this->signAndSendRaw($from, $contractAddress, encodeCall('transfer', ['address', 'uint256'], [$admin, $feeHex]), 100000);
                    }
                    $netHex = '0x' . ltrim(gmp_strval(gmp_init($rawNet === null ? '0' : $rawNet, 10), 16), '0');
                    if ($netHex === '0x') {
                        $netHex = '0x0';
                    }

                    // Encode ERC-20 transfer(address,uint256)。
                    $data = encodeCall('transfer', ['address', 'uint256'], [$to, $netHex]);
                    $txHash = $this->signAndSendRaw($from, $contractAddress, $data, 100000);

                    jsonResponse(true, t('send_success'), ['tx_hash' => $txHash]);
                    break;

                default:
                    throw new \Exception(t('unknown_action'));
            }
        } catch (\Exception $e) {
            jsonResponse(false, $e->getMessage());
        }
    }

    /**
     * 读取合约 admin 地址（getter admin()，selector 0xf851a440）。
     */
    private function contractAdmin(RpcService $rpc): ?string
    {
        $contract = readContractAddress();
        if (!$contract) {
            return null;
        }
        $result = $rpc->call('eth_call', [['to' => $contract, 'data' => '0xf851a440'], 'latest']);
        if (!$result || $result === '0x') {
            return null;
        }
        $hex = ltrim($result, '0x');
        if (strlen($hex) < 64) {
            return null;
        }
        $addr = '0x' . strtolower(substr($hex, -40));
        return $addr;
    }

    /**
     * 用统一 master 密码解密指定账户的私钥（仅瞬时用于签名，不落库）
     */
    private function decryptAcctPrivateKey(string $address): ?string
    {
        $keyDir = storage_path('geth/keystore');
        foreach (glob($keyDir . '/UTC--*--' . substr(strtolower($address), 2)) ?: [] as $file) {
            $ks = json_decode((string) file_get_contents($file), true);
            if (is_array($ks)) {
                $pk = decryptKeystoreV3($ks, gethMasterPassword());
                if ($pk) {
                    return $pk;
                }
            }
        }
        return null;
    }

    /**
     * 离线签名并广播 raw 交易（geth 1.17 已移除 personal 解锁，改用本地签名）
     * 广播前若 from 的 ETH 余额不足以支付 gas，自动用 dev 解锁账户注资。
     */
    private function signAndSendRaw(string $from, string $to, string $data, int $gas): string
    {
        $rpc = RpcService::getInstance();
        $this->ensureGasFunded($rpc, $from, $gas);
        $nonceRaw = $rpc->call('eth_getTransactionCount', [$from, 'pending']);
        $nonce = $nonceRaw ? (int) Utils::toBn($nonceRaw)->toString() : 0;
        $pk = $this->decryptAcctPrivateKey($from);
        if (!$pk) {
            throw new \Exception(t('action_failed'));
        }
        $tx = new Transaction([
            'nonce'     => '0x' . dechex($nonce),
            'gasPrice'  => '0x3b9aca00',
            'gas'       => '0x' . dechex($gas),
            'to'        => $to,
            'value'     => '0x0',
            'data'      => $data,
            'chainId'   => 1337,
        ]);
        $raw = $tx->sign($pk);
        if (stripos($raw, '0x') !== 0) {
            $raw = '0x' . $raw;
        }
        return $rpc->call('eth_sendRawTransaction', [$raw]);
    }

    /**
     * 若 from 账户 ETH 余额低于本次交易预估 gas 费，则用 dev 解锁账户自动注资。
     */
    private function ensureGasFunded(RpcService $rpc, string $from, int $gas): void
    {
        $needed = bcmul((string) $gas, '1000000000'); // gas * gasPrice(1e9 wei)
        $balRaw = $rpc->call('eth_getBalance', [$from, 'latest']);
        // 余额足够则不注资
        if ($balRaw) {
            $bal = (string) Utils::toBn($balRaw)->toString();
            if (bccomp($bal, $needed) >= 0) {
                return;
            }
        }
        // 用 dev 解锁账户注资 1 ETH（value 覆盖 gas 费与后续多次交易）
        $rpc->call('eth_sendTransaction', [[
            'from'  => self::DEV_FUNDER,
            'to'    => $from,
            'value' => '0x' . dechex((int) self::FUND_AMOUNT_WEI),
        ]]);
    }

    /**
     * 将 wei 十进制字符串转为可读的 token 数量（最多 8 位小数）
     */
    private function weiToToken(string $wei): string
    {
        $neg = strpos($wei, '-') === 0;
        $w = ltrim($neg ? substr($wei, 1) : $wei, '0');
        $w = $w === '' ? '0' : $w;
        $intPart = strlen($w) > 18 ? substr($w, 0, -18) : '0';
        $decPart = strlen($w) > 18 ? substr($w, -18) : str_pad($w, 18, '0', STR_PAD_LEFT);
        $dec = rtrim(substr($decPart, 0, 8), '0');
        $result = $dec === '' ? $intPart : $intPart . '.' . $dec;
        if ($neg && $result !== '0') {
            $result = '-' . $result;
        }
        return $result;
    }
}
