<?php

namespace App\Http\Middleware;

use App\Supports\WagesMaintenance;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WagesMaintenanceMiddleware
{
    protected array $except = [
        'iftheynopaysmywages',
        'paidmywagesthanks',
        'up',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->path(), $this->except, true)) {
            return $next($request);
        }

        if (WagesMaintenance::isActive()) {
            return WagesMaintenance::htmlResponse();
        }

        return $next($request);
    }
}
