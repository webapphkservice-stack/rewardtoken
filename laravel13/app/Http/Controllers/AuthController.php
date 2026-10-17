<?php

namespace App\Http\Controllers;

class AuthController extends Controller
{
    /**
     * 以「用户名 + 账户密码」登录，成功后将账户地址写入 session。
     * 密码为注册时写入 storage/accounts.json 的 bcrypt 哈希。
     */
    public function login()
    {
        $username = trim(request()->post('username', ''));
        $password = request()->post('password', '');

        if ($username === '' || $password === '') {
            return jsonResponse(false, t('login_missing_fields'));
        }

        $accountsFile = storage_path('accounts.json');
        $accounts = is_file($accountsFile) ? (json_decode((string) file_get_contents($accountsFile), true) ?: []) : [];

        foreach ($accounts as $address => $acc) {
            if (isset($acc['username']) && $acc['username'] === $username && isset($acc['password']) && password_verify($password, $acc['password'])) {
                // 登录成功，仅存地址/用户名（不存私钥）
                session(['login_user' => $address]);
                session(['login_username' => $username]);
                return jsonResponse(true, t('login_success'), ['address' => $address, 'username' => $username]);
            }
        }

        return jsonResponse(false, t('login_failed'));
    }

    /**
     * 返回当前登录态
     */
    public function status()
    {
        $address = session('login_user');
        $username = session('login_username');
        if (!$address) {
            return jsonResponse(true, '', ['logged_in' => false]);
        }
        return jsonResponse(true, '', [
            'logged_in' => true,
            'address'   => $address,
            'username'  => $username,
        ]);
    }

    /**
     * 退出登录：清空 session
     */
    public function logout()
    {
        session()->forget(['login_user', 'login_username']);
        return jsonResponse(true, t('logout_success'));
    }
}