<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class RewardsController extends Controller
{
    public function index()
    {
        return view('admin.rewards');
    }
}
