<?php

namespace App\Http\Middleware\Client;

use Closure;

class CanClientCreate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = auth()->user();
        return $next($request);
    }
}
