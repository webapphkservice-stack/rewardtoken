<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MyController;

class OrdersController extends Controller
{
    /**
     * 返回当前登录用户的代币购买订单列表（按时间倒序）。
     * 走 /api/orders（POST），复用 MyController::loadUserOrders。
     */
    public function index()
    {
        $address = session('login_user');
        if (! $address) {
            return jsonResponse(false, t('login_required'));
        }

        $orders = MyController::loadUserOrders($address);

        return jsonResponse(true, '', $orders);
    }
}
