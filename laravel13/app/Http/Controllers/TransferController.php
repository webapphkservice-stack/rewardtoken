<?php

namespace App\Http\Controllers;

class TransferController extends Controller
{
    public function index()
    {
        // 登录后才允许访问
        if (!session('login_user')) {
            return redirect('/');
        }
        return view('transfer', [
            'loginAddress'  => session('login_user'),
            'loginUsername' => session('login_username'),
        ]);
    }
}