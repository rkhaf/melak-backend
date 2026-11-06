<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('admin.login')->withErrors([
                'login' => 'Silakan masuk terlebih dahulu untuk mengakses dashboard.',
            ]);
        }

        $user = Auth::user();
        if (! in_array($user->role, ['admin', 'superadmin'])) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->withErrors([
                'login' => 'Akses ditolak. Anda tidak memiliki hak akses Administrator.',
            ]);
        }

        return $next($request);
    }
}
