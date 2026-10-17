<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class NodeController extends Controller
{
    public function index()
    {
        return view('admin.node');
    }
}
