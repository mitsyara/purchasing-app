<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;

class CheckPin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, \Closure $next)
    {
        $data = $request->session()->get('pin_verified');

        $isVerified =
            $data &&
            ($data['value'] ?? false) === true &&
            now()->lt($data['expires_at']);

        if (!$isVerified) {
            $request->session()->forget('pin_verified');
            return redirect()->route('pin.form');
        }

        return $next($request);
    }
}
