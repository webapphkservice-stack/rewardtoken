<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminAuthenticated
{
    /**
     * 管理员鉴权：
     * - 页面请求未登录 → 跳转 /admin/login
     * - /api/* 请求未登录 → 返回与原契约一致的 JSON（success=false），HTTP 401
     */
    public function handle(Request $request, Closure $next)
    {
        if (! $request->session()->get('admin_user')) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'unauthorized',
                ], 401);
            }

            return redirect('/admin/login');
        }

        return $next($request);
    }
}
