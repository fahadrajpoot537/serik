<?php

namespace App\Http\Middleware;

use Botble\RealEstate\Models\Property;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds X-Robots-Tag on individual property detail pages.
 */
final class PropertyNoIndexHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->isPropertyDetailRequest($request)) {
            $response->headers->set('X-Robots-Tag', 'noindex, follow', false);
        }

        return $response;
    }

    private function isPropertyDetailRequest(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        $route = $request->route();
        if (! $route) {
            return false;
        }

        $slugPrefix = \Botble\Slug\Facades\SlugHelper::getPrefix(Property::class, 'properties') ?: 'properties';

        return $request->is($slugPrefix . '/*') && ! $request->is($slugPrefix, $slugPrefix . '/map');
    }
}
