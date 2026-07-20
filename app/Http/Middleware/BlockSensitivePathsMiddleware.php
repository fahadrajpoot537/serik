<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockSensitivePathsMiddleware
{
    /**
     * Deny bot probes for /public/... and known sensitive files, regardless of
     * the web server (works even when .htaccess is ignored, e.g. nginx/Cloudflare).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = ltrim($request->path(), '/');
        $lower = strtolower($path);

        // 1) Block direct /public/... probing
        if ($lower === 'public' || str_starts_with($lower, 'public/')) {
            abort(403);
        }

        // 2) Block dot-files and known sensitive filenames anywhere in the path
        if (preg_match(
            '#(^|/)(\.env[^/]*|\.git|\.svn|\.htaccess|\.htpasswd|composer\.(json|lock)|package(-lock)?\.json|yarn\.lock|artisan|phpunit\.xml|web\.config|Dockerfile|docker-compose\.ya?ml)(/|$)#i',
            $path
        )) {
            abort(403);
        }

        // 3) Block dangerous / backup / dump file extensions
        if (preg_match(
            '#\.(bak|backup|old|orig|save|swp|swo|sql|sqlite|db|dump|zip|tar|tgz|gz|bz2|7z|rar|log|ini|conf|sh|bash|pem|key)$#i',
            $lower
        )) {
            abort(403);
        }

        return $next($request);
    }
}
