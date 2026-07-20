<?php

namespace Botble\RealEstate\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAccountIsNotBlocked
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user('account');

        if (! auth('account')->check()) {
            return redirect()->route('public.account.login');
        }

        if ($user->blocked_at) {
            Auth::guard('account')->logout();

            $message = trans('plugins/real-estate::account.blocked_account_message');

            if ($user->blocked_reason) {
                $message .= ' ' . trans('plugins/real-estate::account.blocked_reason_label', ['reason' => $user->blocked_reason]);
            }

            return redirect()->route('public.account.login')
                ->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
