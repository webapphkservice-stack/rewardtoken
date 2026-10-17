<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeployController as AdminDeployController;
use App\Http\Controllers\Admin\NodeController as AdminNodeController;
use App\Http\Controllers\Admin\RewardsController as AdminRewardsController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Api\BuyApiController;
use App\Http\Controllers\Api\OrdersController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\BuyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MyController;
use App\Http\Controllers\RewardsController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\Web\BlockDataController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| 前台页面
|
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/wallet', [WalletController::class, 'index'])->name('wallet');
Route::get('/rewards', [RewardsController::class, 'index'])->name('rewards');
Route::get('/transfer', [TransferController::class, 'index'])->name('transfer');
Route::get('/block', [BlockController::class, 'index'])->name('block');
Route::get('/my', [MyController::class, 'index'])->name('my');
Route::get('/buy', [BuyController::class, 'index'])->name('buy');

/*
|--------------------------------------------------------------------------
| 代币购买 API（支付网关）
|--------------------------------------------------------------------------
*/

Route::post('/api/payment/webhook', [PaymentWebhookController::class, 'handle']);
Route::post('/api/buy/create-order', [BuyApiController::class, 'createOrder']);
Route::get('/api/buy/query', [BuyApiController::class, 'queryOrder']);

/*
|--------------------------------------------------------------------------
| 登录 / 退出（基于 session，供前端点击“我的”时校验登录态）
|--------------------------------------------------------------------------
*/

Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/status', [AuthController::class, 'status']);
Route::post('/auth/logout', [AuthController::class, 'logout']);

// 区块信息数据接口（需登录，走 web 组以携带 session）
Route::get('/api/block', [BlockDataController::class, 'index']);

// 当前登录用户的购买订单（需登录，走 web 组以读取 session）
Route::post('/api/orders', [OrdersController::class, 'index']);

/*
|--------------------------------------------------------------------------
| 管理后台页面
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/deploy', [AdminDeployController::class, 'index'])->name('admin.deploy');
    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('admin.settings');
    Route::get('/rewards', [AdminRewardsController::class, 'index'])->name('admin.rewards');
    Route::get('/node', [AdminNodeController::class, 'index'])->name('admin.node');
});
