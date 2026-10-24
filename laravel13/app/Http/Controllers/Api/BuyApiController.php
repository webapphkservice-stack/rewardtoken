<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RpcService;
use Web3\Utils;
use Web3p\EthereumTx\Transaction;

class BuyApiController extends Controller
{
    // GMPay merchant credentials
    private const GMPAY_PID = '102173135207';
    private const GMPAY_SECRET = 'gms_SfFSwCa7kQl-RtSPcmacF6Zy114YFDDEcB_1M7fbHio';
    private const GMPAY_BASE_URL = 'http://localhost:3000';

    /**
     * 调用网关时附带公网 Host/X-Forwarded-Proto 头，
     * 使网关生成的 checkoutUrl（payment_url）指向公开域名而非 localhost。
     * 由 .env 的 GMPAY_PUBLIC_HOST 控制；本地开发不配置则维持原行为。
     */
    private function gmpayHeaders(bool $withContentType = true): array
    {
        $headers = $withContentType ? ['Content-Type: application/json'] : [];
        $publicHost = env('GMPAY_PUBLIC_HOST');
        if ($publicHost) {
            $headers[] = 'Host: ' . $publicHost;
            $headers[] = 'X-Forwarded-Proto: ' . (str_starts_with((string) config('app.url'), 'https') ? 'https' : 'http');
        }
        return $headers;
    }

    // Dev funder for gas
    private const DEV_FUNDER = '0x71562b71999873db5b286df957af199ec94617f7';

    public function createOrder()
    {
        try {
            if (!request()->isMethod('POST')) {
                throw new \Exception(t('only_post_allowed'));
            }

            $fromAddress = strtolower(trim((string) session('login_user', '')));
            $qty = (int) request()->post('qty', 0);
            $planIndex = (int) request()->post('plan', 0);

            if (!$fromAddress || $qty < 1) {
                throw new \Exception(t('missing_required_fields'));
            }

            $plans = purchasePlans();
            if (!isset($plans[$planIndex])) {
                $planIndex = 0;
            }
            $priceUsdt = $plans[$planIndex]['price_usdt'];
            $tokenAmount = $plans[$planIndex]['token_amount'];
            $bonusAmount = $plans[$planIndex]['bonus_amount'];
            $totalUsdt = $priceUsdt * $qty;
            $totalUsdtStr = number_format($totalUsdt, 2, '.', '');

            // Generate unique order ID
            $orderId = 'IRT-' . strtoupper(substr($fromAddress, 2, 8)) . '-' . time() . '-' . substr(bin2hex(random_bytes(4)), 0, 8);

            // Build GMPay signature
            $notifyUrl = rtrim((request()->getSchemeAndHttpHost()), '/') . '/api/payment/webhook';
            $params = [
                'pid' => self::GMPAY_PID,
                'order_id' => $orderId,
                'currency' => 'USD',
                'amount' => $totalUsdtStr,
                'notify_url' => $notifyUrl,
            ];
            ksort($params);
            $source = [];
            foreach ($params as $k => $v) {
                if ($v !== '' && $v !== null) {
                    $source[] = $k . '=' . $v;
                }
            }
            $signStr = implode('&', $source);
            $signature = strtolower(hash_hmac('sha256', $signStr, self::GMPAY_SECRET));

            // Call GMPay API
            $payload = array_merge($params, ['signature' => $signature]);
            $ch = curl_init(self::GMPAY_BASE_URL . '/payments/gmpay/v1/order/create-transaction');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => $this->gmpayHeaders(),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 5,
            ]);
            $resp = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($resp === false) {
                throw new \Exception('gmpay connection failed');
            }

            $result = json_decode($resp, true);
            if (!$result || ($httpCode !== 200 && ($result['status_code'] ?? 0) !== 0)) {
                $errMsg = $result['message'] ?? 'gmpay error (HTTP ' . $httpCode . ')';
                throw new \Exception($errMsg);
            }

            // Store order mapping
            $ordersFile = storage_path('orders.json');
            $orders = [];
            if (is_file($ordersFile)) {
                $orders = json_decode((string) file_get_contents($ordersFile), true) ?: [];
            }
            $orders[$orderId] = [
                'address' => $fromAddress,
                'qty' => $qty,
                'plan_index' => $planIndex,
                'total_usdt' => $totalUsdtStr,
                'token_amount' => $tokenAmount * $qty,
                'bonus_amount' => $bonusAmount * $qty,
                'trade_id' => $result['data']['trade_id'] ?? '',
                'status' => 'pending',
                'created_at' => time(),
            ];
            file_put_contents($ordersFile, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

            jsonResponse(true, t('payment_created'), [
                'order_id' => $orderId,
                'trade_id' => $result['data']['trade_id'] ?? '',
                'payment_url' => $result['data']['payment_url'] ?? '',
                'expired_at' => $result['data']['expired_at'] ?? 0,
            ]);

        } catch (\Exception $e) {
            jsonResponse(false, $e->getMessage());
        }
    }

    /**
     * 查询订单状态
     */
    public function queryOrder()
    {
        try {
            $orderId = trim(request()->input('order_id', ''));
            $tradeId = trim(request()->input('trade_id', ''));

            if (!$orderId && !$tradeId) {
                throw new \Exception(t('missing_required_fields'));
            }

            // Build query params
            $params = ['pid' => self::GMPAY_PID];
            if ($tradeId) {
                $params['trade_id'] = $tradeId;
            } else {
                $params['order_id'] = $orderId;
            }
            ksort($params);
            $source = [];
            foreach ($params as $k => $v) {
                if ($v !== '' && $v !== null) {
                    $source[] = $k . '=' . $v;
                }
            }
            $signStr = implode('&', $source);
            $signature = strtolower(hash_hmac('sha256', $signStr, self::GMPAY_SECRET));

            $queryUrl = self::GMPAY_BASE_URL . '/payments/gmpay/v1/order/query?' . http_build_query(array_merge($params, ['signature' => $signature]));

            $ch = curl_init($queryUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $this->gmpayHeaders(false),
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
            ]);
            $resp = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($resp === false) {
                throw new \Exception('gmpay query failed');
            }

            $result = json_decode($resp, true);
            if (!$result || $httpCode !== 200) {
                throw new \Exception($result['message'] ?? 'query error');
            }

            jsonResponse(true, '', $result['data'] ?? null);

        } catch (\Exception $e) {
            jsonResponse(false, $e->getMessage());
        }
    }

    /**
     * 发送 IRT 代币到用户地址（由 Webhook 或手动触发调用）
     * 从管理员账户转账到用户，等待链上 receipt 成功后才返回 true。
     */
    public static function sendTokens(string $toAddress, string $amountWei): bool
    {
        try {
            $contractAddress = readContractAddress();
            if (!$contractAddress) {
                throw new \Exception('contract not deployed');
            }

            $rpc = RpcService::getInstance();

            // Encode transfer(address,uint256)
            $data = self::encodeTransfer($toAddress, $amountWei);

            // Try using dev-mode unlocked account (coinbase)
            $accounts = $rpc->call('eth_accounts');
            $from = !empty($accounts[0]) ? $accounts[0] : null;
            if (!$from) {
                throw new \Exception('no account available');
            }

            $txHash = $rpc->call('eth_sendTransaction', [[
                'from' => $from,
                'to' => $contractAddress,
                'gas' => '0x1e8480', // 2,000,000（合约 transfer 完整逻辑需更高 gas，防止 OOG）
                'gasPrice' => '0x3b9aca00',
                'value' => '0x0',
                'data' => $data,
            ]]);

            if (empty($txHash) || !is_string($txHash)) {
                throw new \Exception('no tx hash returned');
            }

            // 等待链上回执，确认 status === 0x1（成功）才算完成
            for ($i = 0; $i < 30; $i++) {
                $receipt = $rpc->call('eth_getTransactionReceipt', [$txHash]);
                if (is_array($receipt) && isset($receipt['status'])) {
                    return (string) $receipt['status'] === '0x1';
                }
                // receipt 未确认时返回 null / 空，短暂等待后重试
                usleep(2000000);
            }

            throw new \Exception('tx receipt timeout');
        } catch (\Exception $e) {
            error_log('sendTokens failed: ' . $e->getMessage());
            return false;
        }
    }

    private static function encodeTransfer(string $to, string $amountWei): string
    {
        // transfer(address,uint256) selector = 0xa9059cbb
        $to = strtolower(str_starts_with($to, '0x') ? substr($to, 2) : $to);
        $to = str_pad($to, 64, '0', STR_PAD_LEFT);
        // uint256 大数十进制 -> 256bit 十六进制（web3p 标准库）
        $hex = strtolower(Utils::toBn($amountWei)->toHex(true));
        $amount = str_pad($hex, 64, '0', STR_PAD_LEFT);
        return '0xa9059cbb' . $to . $amount;
    }
}