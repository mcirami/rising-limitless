<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use LeadMax\TrackYourStats\User\User;

class LegacyUserAuth
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle( Request $request, Closure $next): mixed {
        $user = new User();
        // Failed access checks should redirect without trying to clean up an
        // already-expired or partially missing legacy session.
        if ($user->verify_login_session(false)) {
            return $next($request);
        } else {
            return redirect('/login');
        }
    }
}
