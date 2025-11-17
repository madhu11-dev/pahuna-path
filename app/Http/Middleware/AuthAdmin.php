<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AuthAdmin
{

    public function handle(Request $request, Closure $next)
    {

        if (Auth::check()) {

            if (Auth::user()->utype === 'ADM') {
                return $next($request);
            } else {
                Session::flash('error', 'You do not have admin access');
                return redirect()->route('login');
            }
        }
        return redirect()->route('login');
    }
}
