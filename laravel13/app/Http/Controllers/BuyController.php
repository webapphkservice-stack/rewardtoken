<?php

namespace App\Http\Controllers;

class BuyController extends Controller
{
    public function index()
    {
        $config = getSiteConfig();
        return view('buy', [
            'priceUsdt' => $config['token_purchase_price_usdt'] ?? 10,
            'tokenAmount' => $config['token_purchase_amount'] ?? 1000,
            'bonusAmount' => $config['token_purchase_bonus'] ?? 0,
            'symbol' => $config['symbol'] ?? 'IRT',
        ]);
    }
}