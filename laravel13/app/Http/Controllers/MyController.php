<?php

namespace App\Http\Controllers;

class MyController extends Controller
{
    public function index()
    {
        $loginAddress = session('login_user');
        $loginUsername = session('login_username');

        return view('my', [
            'loginAddress' => $loginAddress,
            'loginUsername' => $loginUsername,
            'orders' => self::loadUserOrders($loginAddress),
        ]);
    }

    /**
     * 读取当前登录用户的代币购买订单（按时间倒序）
     */
    public static function loadUserOrders(?string $address): array
    {
        if (! $address) {
            return [];
        }

        $file = storage_path('orders.json');
        if (! is_file($file)) {
            return [];
        }

        $all = json_decode((string) file_get_contents($file), true) ?: [];
        $addr = strtolower($address);
        $orders = [];

        foreach ($all as $oid => $o) {
            if (! is_array($o)) {
                continue;
            }
            if (strtolower(trim((string) ($o['address'] ?? ''))) === $addr) {
                $orders[] = array_merge(['order_id' => $oid], $o);
            }
        }

        usort($orders, fn ($a, $b) => (int) ($b['created_at'] ?? 0) <=> (int) ($a['created_at'] ?? 0));

        return $orders;
    }
}
