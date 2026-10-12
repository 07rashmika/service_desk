<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UseAccountSetupBroker
{
    /**
     * When a new user submits the "set your password" form from their welcome email,
     * check their token against the longer-lived welcome tokens instead of the
     * password reset tokens. A reset token isn't valid there, and vice versa.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('password.update') && $request->boolean('welcome')) {
            config(['fortify.passwords' => 'welcome']);
        }

        return $next($request);
    }
}
