<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class PaymentWebhookController extends Controller
{
    // Must match BuyApiController
    private const GMPAY_PID = '102173135207';
    private const GMPAY_SECRET = 'gms_SfFSwCa7kQl-RtSPcmacF6Zy114YFDDEcB_1M7fbHio';

    public function handle()
    {
        // GMPay sends JSON POST
        $rawBody = file_get_contents('php://input');
        $payload = json_decode($rawBody, true);

        if (!$payload || !is_array($payload)) {
            http_response_code(400);
            echo 'invalid payload';
            return;
        }

        try {
            // 1. Verify HMAC-SHA256 signature
            if (!$this->verifySignature($payload)) {
                http_response_code(401);
                echo 'signature mismatch';
                return;
            }

            // 2. Verify merchant pid
            if (($payload['pid'] ?? '') !== self::GMPAY_PID) {
                http_response_code(400);
                echo 'wrong pid';
                return;
            }

            $orderId = $payload['order_id'] ?? '';
            $status = (int) ($payload['status'] ?? 0);
            $tradeId = (string) ($payload['trade_id'] ?? '');
            $amount = (string) ($payload['amount'] ?? '');
            $currency = (string) ($payload['currency'] ?? '');

            if (!$orderId) {
                http_response_code(400);
                echo 'missing order_id';
                return;
            }

            $ordersFile = storage_path('orders.json');

            // 3. 文件独占锁：覆盖 读->校验->发币->写 全流程，
            //    多个回调串行处理，结合 processing/completed 状态机保证幂等，不重复发币。
            $handle = fopen($ordersFile, 'c+');
            if ($handle === false) {
                http_response_code(500);
                echo 'lock failed';
                return;
            }
            if (!flock($handle, LOCK_EX)) {
                fclose($handle);
                http_response_code(500);
                echo 'lock busy';
                return;
            }

            $orders = [];
            $content = stream_get_contents($handle);
            if ($content !== '') {
                $orders = json_decode($content, true) ?: [];
            }

            // 订单不存在：可能来自其它商户，直接 ack
            if (!isset($orders[$orderId])) {
                flock($handle, LOCK_UN);
                fclose($handle);
                http_response_code(200);
                echo 'ok';
                return;
            }

            $order = &$orders[$orderId];

            // 4. 幂等：已完成/处理中直接 ack，不重复发币
            $cur = $order['status'] ?? '';
            if ($cur === 'completed' || $cur === 'processing') {
                flock($handle, LOCK_UN);
                fclose($handle);
                http_response_code(200);
                echo 'ok';
                return;
            }

            // 5. 校验回调字段与本地订单一致
            $localAmount = number_format((float) ($order['total_usdt'] ?? 0), 2, '.', '');
            if ($currency !== '' && strtoupper($currency) !== 'USD') {
                flock($handle, LOCK_UN);
                fclose($handle);
                http_response_code(400);
                echo 'currency mismatch';
                return;
            }
            if ($amount !== '' && abs((float) $amount - (float) $localAmount) > 0.001) {
                $order['status'] = 'amount_mismatch';
                $order['error'] = 'callback amount mismatch';
                $this->writeOrders($handle, $orders);
                flock($handle, LOCK_UN);
                fclose($handle);
                http_response_code(200);
                echo 'ok';
                return;
            }
            if (!empty($order['trade_id']) && $tradeId !== '' && (string) $order['trade_id'] !== $tradeId) {
                $order['status'] = 'trade_mismatch';
                $order['error'] = 'callback trade_id mismatch';
                $this->writeOrders($handle, $orders);
                flock($handle, LOCK_UN);
                fclose($handle);
                http_response_code(200);
                echo 'ok';
                return;
            }

            // 6. 先标记 processing 落盘，崩溃后不会重复发币
            $order['status'] = 'processing';
            $this->writeOrders($handle, $orders);

            // 7. 按状态处理
            if ($status === 2) {
                $toAddress = $order['address'] ?? '';
                $totalToken = (int) ($order['token_amount'] ?? 0) + (int) ($order['bonus_amount'] ?? 0);
                if (!$toAddress || $totalToken <= 0) {
                    $order['status'] = 'failed';
                    $order['error'] = 'invalid order data';
                } else {
                    // Convert to wei (18 decimals)
                    $amountWei = bcmul((string) $totalToken, '1000000000000000000');

                    // sendTokens 内部等待链上 receipt status==0x1 成功才返回 true
                    $ok = BuyApiController::sendTokens($toAddress, $amountWei);
                    if ($ok) {
                        $order['status'] = 'completed';
                        if ($tradeId !== '') $order['trade_id'] = $tradeId;
                        $order['completed_at'] = time();
                    } else {
                        $order['status'] = 'transfer_failed';
                        if ($tradeId !== '') $order['trade_id'] = $tradeId;
                        $order['error'] = 'token transfer failed';
                    }
                }
            } elseif ($status === 3) {
                $order['status'] = 'closed';
            }

            $this->writeOrders($handle, $orders);
            flock($handle, LOCK_UN);
            fclose($handle);

            // Always return ok to acknowledge receipt
            http_response_code(200);
            echo 'ok';

        } catch (\Exception $e) {
            error_log('PaymentWebhook error: ' . $e->getMessage());
            http_response_code(500);
            echo 'internal error';
        }
    }

    /**
     * 在持有文件锁的句柄上回写订单数据（不释放锁）
     */
    private function writeOrders($handle, array $orders): void
    {
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fflush($handle);
    }

    /**
     * Verify GMPay HMAC-SHA256 signature
     */
    private function verifySignature(array $payload): bool
    {
        $receivedSig = $payload['signature'] ?? '';
        if (!$receivedSig) {
            return false;
        }

        // Sort keys (exclude signature), filter null/empty
        $params = [];
        foreach ($payload as $key => $value) {
            if ($key === 'signature') {
                continue;
            }
            if ($value === null || $value === '') {
                continue;
            }
            $params[$key] = $value;
        }
        ksort($params);

        $source = [];
        foreach ($params as $key => $value) {
            $source[] = $key . '=' . $value;
        }
        $signStr = implode('&', $source);

        $expectedSig = strtolower(hash_hmac('sha256', $signStr, self::GMPAY_SECRET));

        return hash_equals($expectedSig, strtolower($receivedSig));
    }
}