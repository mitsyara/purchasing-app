<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LockSessionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, \Closure $next): Response
    {
        // Kiểm tra session 'screen_locked'
        if ($request->user() && session('screen_locked', false)) {
            // Chia sẻ flag cho view/Livewire
            view()->share('screen_locked', true);
        }

        return $next($request);
    }
}
