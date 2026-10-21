<?php

use App\Http\Controllers\Api\AbiController;
use App\Http\Controllers\Api\DeployController;
use App\Http\Controllers\Api\GethWalletController;
use App\Http\Controllers\Api\NodeController;
use App\Http\Controllers\Api\RewardsController;
use App\Http\Controllers\Api\RunTestController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\SiteSettingsController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\WalletsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| 与原 api/*.php 保持相同的 JSON 响应契约。
| api 路由默认不启用 CSRF，与原实现行为一致。
|
*/

Route::get('/status', [StatusController::class, 'index']);
Route::get('/rewards', [RewardsController::class, 'index']);
Route::get('/wallets', [WalletsController::class, 'index']);
Route::get('/abi', [AbiController::class, 'index']);
Route::match(['get', 'post'], '/geth-wallet', [GethWalletController::class, 'index']);

/*
|--------------------------------------------------------------------------
| 管理端 API（需管理员登录态，未登录返回 401 JSON）
|--------------------------------------------------------------------------
*/

Route::middleware([
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Session\Middleware\StartSession::class,
    'admin',
])->group(function () {
    Route::match(['get', 'post'], '/site-settings', [SiteSettingsController::class, 'index']);
    Route::post('/deploy', [DeployController::class, 'index']);
    Route::post('/settings', [SettingsController::class, 'index']);
    Route::match(['get', 'post'], '/node', [NodeController::class, 'index']);
    Route::post('/run-test', [RunTestController::class, 'index']);
});
