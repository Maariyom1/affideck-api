<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function items(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->integer('per_page', 15)));
        
        $paginator = MarketplaceItem::query()
            ->where('status', 'published')
            ->latest()
            ->paginate($perPage);
        
        return response()->json([
            'data' => $paginator->getCollection(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function showItem(string $itemId): JsonResponse
    {
        $item = MarketplaceItem::query()->where('status', 'published')->findOrFail($itemId);
        
        return response()->json([
            'data' => $item,
        ]);
    }

    public function storeItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'category' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
        ]);
        
        $item = $request->user()->marketplaceItems()->create($data);
        
        return response()->json([
            'data' => $item,
        ], 201);
    }

    public function updateItem(Request $request, string $itemId): JsonResponse
    {
        $item = MarketplaceItem::query()->findOrFail($itemId);
        
        if ($item->seller_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $item->update($request->only(['title', 'description', 'price', 'category', 'images']));
        
        return response()->json([
            'data' => $item,
        ]);
    }

    public function destroyItem(Request $request, string $itemId): JsonResponse
    {
        $item = MarketplaceItem::query()->findOrFail($itemId);
        
        if ($item->seller_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $item->delete();
        
        return response()->noContent();
    }

    public function buyItem(Request $request, string $itemId): JsonResponse
    {
        $item = MarketplaceItem::query()->findOrFail($itemId);
        
        $data = $request->validate([
            'payment_method_id' => ['required', 'string'],
            'qty' => ['required', 'integer', 'min:1'],
            'shipping' => ['nullable', 'array'],
        ]);
        
        $orderId = uniqid('order_');
        $paymentIntentId = uniqid('pi_');
        
        return response()->json([
            'data' => [
                'order_id' => $orderId,
                'payment_intent_id' => $paymentIntentId,
            ],
        ], 201);
    }

    public function orders(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->integer('per_page', 15)));
        
        return response()->json([
            'data' => [],
            'meta' => [
                'current_page' => 1,
                'per_page' => $perPage,
                'total' => 0,
                'last_page' => 1,
            ],
        ]);
    }
}
