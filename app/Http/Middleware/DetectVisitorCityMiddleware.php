<?php

namespace App\Http\Middleware;

use App\Support\VisitorIpLocation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectVisitorCityMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->cookie('serik_visitor_city')) {
            $location = VisitorIpLocation::resolveFromIp((string) $request->ip());
            $city = trim((string) ($location['city'] ?? ''));

            if ($city !== '') {
                $_COOKIE['serik_visitor_city'] = $city;
                $request->cookies->set('serik_visitor_city', $city);
            }
        }

        $response = $next($request);

        if ($request->cookie('serik_visitor_city')) {
            return $response;
        }

        $location = VisitorIpLocation::resolveFromIp((string) $request->ip());
        $city = trim((string) ($location['city'] ?? ''));

        if ($city === '') {
            return $response;
        }

        return $response->withCookie(cookie(
            'serik_visitor_city',
            $city,
            60 * 24 * 30,
            '/',
            null,
            $request->isSecure(),
            false,
            false,
            'Lax'
        ));
    }
}
