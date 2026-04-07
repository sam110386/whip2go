<?php

namespace App\Http\Middleware\Legacy;

use Closure;
use Illuminate\Http\Request;

class EnsureCloudAdminSession
{
    public function handle(Request $request, Closure $next): mixed
    {
        // Bypass the cloud login page.
        if ($request->segment(2) === 'admins' && in_array($request->segment(3), ['login', 'admin_login'], true)) {
            return $next($request);
        }

        $admin = session()->get('SESSION_ADMIN');
        if (empty($admin) || ($admin['slug'] ?? null) !== 'cloud') {
            return redirect('/admin/admins/login');
        }

        return $next($request);
    }
}

