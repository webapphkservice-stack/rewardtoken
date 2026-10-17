<?php

namespace App\Http\Controllers;

class BlockController extends Controller
{
    public function index()
    {
        // 区块信息公开查看，无需登录
        return view('block');
    }
}