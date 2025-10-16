<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Kiểm tra session PIN
        $pinEntered = $request->session()->get('pin_verified', false);

        if (!$pinEntered && auth()->guest()) {
            // Chưa nhập PIN hoặc PIN sai → redirect sang trang nhập PIN
            return redirect()->route('pin.form');
        }

        // Đã nhập PIN → cho phép tiếp tục
        return $next($request);
    }
}
