<?php

namespace Botble\RealEstate\Http\Middleware;

use Botble\RealEstate\Supports\AccountRegistrationExpiry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAccountRegistrationNotExpired
{
    public function handle(Request $request, Closure $next)
    {
        $account = $request->user('account');

        if (! $account) {
            return $next($request);
        }

        if (AccountRegistrationExpiry::deleteIfExpired($account)) {
            Auth::guard('account')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => true,
                    'message' => AccountRegistrationExpiry::expiredMessage(),
                    'registration_expired' => true,
                ], 401);
            }

            return redirect()
                ->route('public.index')
                ->withErrors(['email' => AccountRegistrationExpiry::expiredMessage()]);
        }

        return $next($request);
    }
}
