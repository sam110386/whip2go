<?php

namespace App\Http\Middleware\Legacy;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class EnsureUserSession
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!session()->has('userid')) {
            return redirect('/logins/index');
        }

        return $next($request);
    }
}

