<?php

namespace App\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Production security headers — additive, permissive enough for Maps / GHL / CDNs.
 * Does not alter response bodies or business logic.
 */
final class SerikSecurityHeaders
{
    /**
     * Apply enterprise security headers to any response (including EarlyHomepageCache HIT).
     */
    public static function apply(Response $response, ?Request $request = null): Response
    {
        if (! config('serik.security.headers_enabled', true)) {
            return $response;
        }

        // Basics (idempotent with Botble HttpSecurityHeaders).
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        $response->headers->set(
            'Permissions-Policy',
            (string) config(
                'serik.security.permissions_policy',
                'accelerometer=(), camera=(), geolocation=(self), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()'
            )
        );

        $response->headers->set(
            'Cross-Origin-Resource-Policy',
            (string) config('serik.security.corp', 'same-site')
        );

        // Allow popups (payment/auth) without full cross-origin isolation.
        $response->headers->set(
            'Cross-Origin-Opener-Policy',
            (string) config('serik.security.coop', 'same-origin-allow-popups')
        );

        // COEP deliberately omitted — breaks many third-party embeds/maps.

        $secure = $request?->isSecure()
            || str_starts_with((string) config('app.url'), 'https://');

        if ($secure && config('serik.security.hsts_enabled', true)) {
            $maxAge = max(300, (int) config('serik.security.hsts_max_age', 31536000));
            $hsts = 'max-age=' . $maxAge . '; includeSubDomains';
            if (config('serik.security.hsts_preload', false)) {
                $hsts .= '; preload';
            }
            $response->headers->set('Strict-Transport-Security', $hsts);
        }

        if (config('serik.security.csp_enabled', true)) {
            $csp = (string) config('serik.security.csp');
            if ($csp !== '') {
                // upgrade-insecure-requests on plain HTTP can force HTTPS navigations
                // and drop non-Secure session cookies (local/php -S / mixed deploys).
                if (! $secure) {
                    $csp = trim(preg_replace('/;?\s*upgrade-insecure-requests\s*/i', '; ', $csp), '; ');
                }
                $header = config('serik.security.csp_report_only', false)
                    ? 'Content-Security-Policy-Report-Only'
                    : 'Content-Security-Policy';
                $response->headers->set($header, $csp);
            }
        }

        return $response;
    }
}
