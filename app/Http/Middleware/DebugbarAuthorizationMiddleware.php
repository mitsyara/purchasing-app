<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DebugbarAuthorizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, \Closure $next): Response
    {
        $restrictedPath = $request->getPathInfo() !== "/purchasing/customs-data/customs-data";
        
        if (auth()->id() === 1) {
            debugbar()->enable();
        } else {
            debugbar()->disable();
        }

        return $next($request);
    }
}
