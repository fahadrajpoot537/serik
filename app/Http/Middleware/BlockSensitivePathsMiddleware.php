<?php

namespace App\Http\Middleware;

use App\Support\PublicPrefixPath;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class BlockSensitivePathsMiddleware
{
    /**
     * Redirect harmless /public/... probes to clean URLs; block sensitive paths.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = ltrim($request->path(), '/');

        $remainder = PublicPrefixPath::stripPrefix($path);
        if ($remainder !== null) {
            if (PublicPrefixPath::isBlockedRemainder($remainder)) {
                $this->logBlockedPublicProbe($request, $remainder);

                return response('Not Found', Response::HTTP_NOT_FOUND);
            }

            $target = PublicPrefixPath::redirectTarget($remainder);
            $query = $request->getQueryString();

            if ($query) {
                $target .= '?' . $query;
            }

            return redirect($target, 301);
        }

        if (preg_match(
            '#(^|/)(\.env[^/]*|\.git|\.svn|\.htaccess|\.htpasswd|composer\.(json|lock)|package(-lock)?\.json|yarn\.lock|artisan|phpunit\.xml|web\.config|Dockerfile|docker-compose\.ya?ml)(/|$)#i',
            $path
        )) {
            abort(403);
        }

        if (preg_match(
            '#\.(bak|backup|old|orig|save|swp|swo|sql|sqlite|db|dump|zip|tar|tgz|gz|bz2|7z|rar|log|ini|conf|sh|bash|pem|key)$#i',
            strtolower($path)
        )) {
            abort(403);
        }

        return $next($request);
    }

    protected function logBlockedPublicProbe(Request $request, string $remainder): void
    {
        try {
            Log::warning('Blocked sensitive /public/ probe', [
                'path' => $request->path(),
                'remainder' => $remainder,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable) {
            // Never break the response if logging fails.
        }
    }
}
