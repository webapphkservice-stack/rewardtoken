<?php

namespace App\Http\Controllers;

class BuyController extends Controller
{
    public function index()
    {
        $config = getSiteConfig();
        return view('buy', [
            'plans' => purchasePlans(),
            'symbol' => $config['symbol'] ?? 'IRT',
        ]);
    }
}