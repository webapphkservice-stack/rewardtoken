<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class DeployController extends Controller
{
    public function index()
    {
        return view('admin.deploy');
    }
}
