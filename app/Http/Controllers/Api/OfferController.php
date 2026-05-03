<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OfferResource;
use App\Models\Offer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->integer('per_page', 15)));
        
        $query = Offer::query()->where('status', 'published');
        
        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->string('q').'%');
        }
        
        if ($request->filled('category')) {
            $query->whereJsonContains('categories', $request->string('category'));
        }
        
        if ($request->filled('geo')) {
            $query->whereJsonContains('geo', $request->string('geo'));
        }
        
        $sort = $request->string('sort', 'newest');
        match ($sort) {
            'popular' => $query->orderByDesc('clicks'),
            'highest_payout' => $query->orderByDesc('payout'),
            default => $query->latest(),
        };
        
        $paginator = $query->paginate($perPage);
        
        return response()->json([
            'data' => OfferResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(string $offerId): JsonResponse
    {
        $offer = Offer::query()->where('status', 'published')->findOrFail($offerId);
        
        return response()->json([
            'data' => new OfferResource($offer),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:cpa,cpc,cpv'],
            'payout' => ['required', 'numeric', 'min:0.01'],
            'tags' => ['nullable', 'array'],
            'categories' => ['nullable', 'array'],
            'geo' => ['nullable', 'array'],
        ]);
        
        $offer = $request->user()->offers()->create($data);
        
        return response()->json([
            'data' => new OfferResource($offer),
        ], 201);
    }

    public function update(Request $request, string $offerId): JsonResponse
    {
        $offer = Offer::query()->findOrFail($offerId);
        
        if ($offer->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $data = $request->validate([
            'name' => ['string', 'max:255'],
            'description' => ['nullable', 'string'],
            'payout' => ['numeric', 'min:0.01'],
            'status' => ['in:draft,published,archived'],
        ]);
        
        $offer->update($data);
        
        return response()->json([
            'data' => new OfferResource($offer),
        ]);
    }

    public function destroy(Request $request, string $offerId): JsonResponse
    {
        $offer = Offer::query()->findOrFail($offerId);
        
        if ($offer->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $offer->delete();
        
        return response()->noContent();
    }

    public function trackingLink(Request $request, string $offerId): JsonResponse
    {
        $offer = Offer::query()->where('status', 'published')->findOrFail($offerId);
        
        $trackingUrl = route('offer.track', ['offer' => $offerId, 'token' => uniqid()]);
        
        return response()->json([
            'data' => [
                'tracking_url' => $trackingUrl,
            ],
        ]);
    }

    public function analytics(Request $request, string $offerId): JsonResponse
    {
        $offer = Offer::query()->findOrFail($offerId);
        
        if ($offer->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json([
            'data' => [
                'clicks' => $offer->clicks,
                'conversions' => $offer->conversions,
                'earnings' => $offer->earnings,
                'epc' => $offer->clicks > 0 ? $offer->earnings / $offer->clicks : 0,
            ],
        ]);
    }

    public function requestApproval(Request $request, string $offerId): JsonResponse
    {
        $offer = Offer::query()->findOrFail($offerId);
        if ($offer->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $approval = \App\Models\OfferApproval::create([
            'offer_id' => $offer->id,
            'requested_by' => $request->user()->id,
            'requested_at' => now(),
            'status' => 'pending',
        ]);
        $offer->status = 'pending_approval';
        $offer->save();
        return response()->json(['data' => $approval], 201);
    }

    public function favorite(Request $request, string $offerId): JsonResponse
    {
        $offer = Offer::query()->where('status', 'published')->findOrFail($offerId);
        $fav = \App\Models\OfferFavorite::firstOrCreate(['offer_id' => $offer->id, 'user_id' => $request->user()->id]);
        return response()->json(['data' => $fav], 201);
    }
}
