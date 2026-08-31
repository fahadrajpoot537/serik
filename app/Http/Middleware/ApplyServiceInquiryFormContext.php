<?php

namespace App\Http\Middleware;

use App\Support\AgentInquiryFormContext;
use App\Support\ServiceInquiryFormContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApplyServiceInquiryFormContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isContactSend($request)) {
            return $next($request);
        }

        if (ServiceInquiryFormContext::isActive($request)) {
            ServiceInquiryFormContext::applyTrustedRequestOverrides($request);

            return $this->throttle($request, $next, 'service-inquiry-form:');
        }

        if (AgentInquiryFormContext::isActive($request)) {
            AgentInquiryFormContext::applyTrustedRequestOverrides($request);

            return $this->throttle($request, $next, 'agent-inquiry-form:');
        }

        return $next($request);
    }

    private function throttle(Request $request, Closure $next, string $prefix): Response
    {
        $limitKey = $prefix . sha1(
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
