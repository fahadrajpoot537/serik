<?php

namespace App\Http\Middleware;

use App\Support\HomepageResponseCache;
use App\Support\SerikGuestPageCache;
use App\Support\SerikHomepageAssets;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serve cached homepage HTML when available (measured ~10s render → ~5ms cache hit).
 * Also stores guest HTML for static CMS/blog paths (SerikGuestPageCache).
 */
class CacheHomepageResponseMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $cached = HomepageResponseCache::get($request);

        if ($cached !== null) {
            $etag = HomepageResponseCache::getEtag($request) ?: ('"' . sha1($cached) . '"');
            if (trim((string) $request->headers->get('If-None-Match')) === $etag) {
                $notModified = response('', 304, [
                    'ETag' => $etag,
                    'X-Serik-Homepage-Cache' => 'HIT-304',
                ]);
                \App\Support\SerikHtmlCacheHeaders::apply($notModified, $request);

                return $notModified;
            }

            $hit = response($cached, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'ETag' => $etag,
                'X-Serik-Homepage-Cache' => 'HIT',
            ]);
            \App\Support\SerikHtmlCacheHeaders::apply($hit, $request);

            return $hit;
        }

        // Guest static pages: serve after StartSession when early layer was bypassed
        // only if the visitor is still anonymous (no account auth).
        if (
            ! auth()->check()
            && ! (is_plugin_active('real-estate') && auth('account')->check())
        ) {
            $guestKey = SerikGuestPageCache::key($request);
            $guestHtml = SerikGuestPageCache::get($guestKey);
            if ($guestHtml !== null) {
                $hit = response($guestHtml, 200, [
                    'Content-Type' => 'text/html; charset=UTF-8',
                    'X-Serik-Guest-Cache' => 'HIT',
                ]);
                \App\Support\SerikHtmlCacheHeaders::apply($hit, $request);

                return $hit;
            }
        }

        $response = $next($request);

        if (
            HomepageResponseCache::isCacheableRequest($request)
            && $response->getStatusCode() === 200
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')
        ) {
            $html = HomepageResponseCache::alignLoopbackOrigins((string) $response->getContent(), $request);
            $html = SerikHomepageAssets::optimizeDocumentHtml($html);
            $response->setContent($html);
            HomepageResponseCache::put($request, $html);
            $response->headers->set('X-Serik-Homepage-Cache', 'MISS');
            \App\Support\SerikHtmlCacheHeaders::apply($response, $request);
            $etag = HomepageResponseCache::getEtag($request);
            if ($etag) {
                $response->headers->set('ETag', $etag);
            }
        } elseif (
            $response->getStatusCode() === 200
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            && ! auth()->check()
            && ! (is_plugin_active('real-estate') && auth('account')->check())
        ) {
            $guestKey = SerikGuestPageCache::key($request);
            if ($guestKey !== null) {
                $html = HomepageResponseCache::alignLoopbackOrigins((string) $response->getContent(), $request);
                SerikGuestPageCache::put($guestKey, $html);
                $response->headers->set('X-Serik-Guest-Cache', 'MISS');
                \App\Support\SerikHtmlCacheHeaders::apply($response, $request);
            }
        }

        return $response;
    }
}
