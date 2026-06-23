<?php

namespace App\Http\Middleware;

use App\Http\Middleware\RedirectIfAuthenticated as Middleware;

class RedirectIfAuthenticated extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected function redirectTo($request)
    {
        return '/';
    }
}
