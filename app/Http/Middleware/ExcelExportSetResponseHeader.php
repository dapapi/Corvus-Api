<?php

namespace App\Http\Middleware;

use Closure;

class ExcelExportSetResponseHeader
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
        $response = $next($request);
        $response->headers->set('Access-Control-Expose-Headers', 'Content-Disposition');
        return $response;
    }
}
