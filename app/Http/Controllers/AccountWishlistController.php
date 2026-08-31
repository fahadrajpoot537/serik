<?php

namespace App\Http\Controllers;

use App\Support\AccountWishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountWishlistController extends Controller
{
    public function state(Request $request): JsonResponse
    {
        $accountId = $this->accountId();
        if (! $accountId) {
            return response()->json([
                'authenticated' => false,
                'ids' => [],
                'count' => 0,
            ], 401);
        }

        $this->importLegacyCookie($request, $accountId);

        $ids = AccountWishlist::propertyIdsFor($accountId);

        return response()->json([
            'authenticated' => true,
            'ids' => $ids,
            'count' => count($ids),
        ])->header('Cache-Control', 'private, no-store, no-cache, must-revalidate');
    }

    public function toggle(Request $request): JsonResponse
    {
        $accountId = $this->accountId();
        if (! $accountId) {
            return response()->json([
                'error' => true,
                'login_required' => true,
                'message' => 'Please log in to save properties.',
            ], 401);
        }

        if (! AccountWishlist::tableReady()) {
            return response()->json([
                'error' => true,
                'message' => 'Wishlist is temporarily unavailable.',
            ], 503);
        }

        $validated = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
            'type' => ['nullable', 'in:property'],
            'action' => ['nullable', 'in:add,remove,toggle'],
        ]);

        $propertyId = (int) $validated['id'];
        $action = $validated['action'] ?? 'toggle';

        if (! AccountWishlist::isEligibleProperty($propertyId)) {
            return response()->json([
                'error' => true,
                'message' => 'This property cannot be saved.',
            ], 422);
        }

        $result = match ($action) {
            'add' => AccountWishlist::add($accountId, $propertyId),
            'remove' => AccountWishlist::remove($accountId, $propertyId),
            default => AccountWishlist::toggle($accountId, $propertyId),
        };

        if (! empty($result['failed'])) {
            return response()->json([
                'error' => true,
                'message' => 'This property could not be saved.',
            ], 503);
        }

        return response()->json([
            'error' => false,
            'saved' => $result['saved'],
            'count' => $result['count'],
            'id' => $propertyId,
        ])->header('Cache-Control', 'private, no-store, no-cache, must-revalidate');
    }

    private function accountId(): ?int
    {
        $id = Auth::guard('account')->id();

        return $id ? (int) $id : null;
    }

    private function importLegacyCookie(Request $request, int $accountId): void
    {
        $raw = (string) $request->cookie('wishlist', '');
        if ($raw === '') {
            return;
        }

        $ids = array_values(array_filter(array_map('intval', explode(',', $raw))));
        if ($ids === []) {
            return;
        }

        AccountWishlist::importPropertyIds($accountId, $ids);
    }
}
