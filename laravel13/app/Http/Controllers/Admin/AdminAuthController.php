<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        if (session('admin_user')) {
            return redirect('/admin');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('username', $credentials['username'])->first();

        if (! $admin || ! Hash::check($credentials['password'], $admin->password)) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['login' => t('login_failed')]);
        }

        $request->session()->regenerate();
        $request->session()->put('admin_user', [
            'id' => $admin->id,
            'username' => $admin->username,
        ]);

        return redirect('/admin');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin_user');
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}
