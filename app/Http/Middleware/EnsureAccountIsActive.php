<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out anyone whose account was deactivated while they were signed in
 * (an open tab, a "remember me" cookie), on their very next request.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if ($user instanceof User && $user->isDeactivated()) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'This account has been deactivated. Ask an admin if you need it back.';

            if ($request->expectsJson()) {
                abort(403, $message);
            }

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
