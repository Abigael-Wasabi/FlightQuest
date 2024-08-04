<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class Authenticate extends Middleware
{
    public function handle($request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        //if user is authenticated,the middleware allows the request to proceed
        return $next($request);
    }
}
