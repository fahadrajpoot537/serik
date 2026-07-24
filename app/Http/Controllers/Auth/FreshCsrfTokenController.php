<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Returns a fresh CSRF token for the current session (used when homepage HTML is cached).
 */
final class FreshCsrfTokenController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'token' => csrf_token(),
        ]);
    }
}
