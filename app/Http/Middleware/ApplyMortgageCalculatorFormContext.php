<?php

namespace App\Http\Middleware;

use App\Support\MortgageCalculatorFormContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApplyMortgageCalculatorFormContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isContactSend($request) || ! MortgageCalculatorFormContext::isWhitelistedContext($request)) {
            return $next($request);
        }

        MortgageCalculatorFormContext::applyTrustedRequestOverrides($request);

        $limitKey = 'mortgage-calculator-form:' . sha1(
            $request->ip() . '|' . strtolower(trim((string) $request->input('email', '')))
        );

        if (RateLimiter::tooManyAttempts($limitKey, 8)) {
            $retry = RateLimiter::availableIn($limitKey);

            return response()->json([
                'error' => true,
                'message' => 'Too many requests. Please try again shortly.',
            ], 429)->header('Retry-After', (string) $retry);
        }

        RateLimiter::hit($limitKey, 60);

        return $next($request);
    }

    private function isContactSend(Request $request): bool
    {
        if (! $request->isMethod('POST')) {
            return false;
        }

        return $request->routeIs('public.send.contact')
            || $request->is('contact/send');
    }
}
