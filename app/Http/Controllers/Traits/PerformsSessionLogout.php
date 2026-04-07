<?php

namespace App\Http\Controllers\Traits;

trait PerformsSessionLogout
{
    protected function performSessionLogout(string $redirectTo = '/admin/admins/login')
    {
        session()->flush();
        return redirect($redirectTo);
    }
}

