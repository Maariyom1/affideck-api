<?php

namespace App\Http\Controllers\Api;

use App\Models\Activity;
use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $offers = Offer::where('user_id', $user->id)->get();
        
        return response()->json([
            'data' => [
                'earnings' => $offers->sum('earnings'),
                'clicks' => $offers->sum('clicks'),
                'conversions' => $offers->sum('conversions'),
                'epc' => $offers->sum('clicks') > 0 ? $offers->sum('earnings') / $offers->sum('clicks') : 0,
                'delta24h' => 0,
                'balance' => $user->balance ?? 0,
            ],
        ]);
    }

    public function chart(Request $request): JsonResponse
    {
        $range = $request->string('range', '7d');
        
        $data = [];
        
        return response()->json([
            'data' => $data,
        ]);
    }

    public function activity(Request $request): JsonResponse
    {
        $limit = max(1, min(20, (int) $request->integer('limit', 8)));
        $user = $request->user();
        $windowKey = intdiv(now()->unix(), 10);
        $cacheKey = 'dashboard:activity:last-window-key:'.$user->id;
        $previousWindowKey = Cache::get($cacheKey);
        $hasNewData = $previousWindowKey === null || (int) $previousWindowKey !== $windowKey;

        // Fetch real user activities from the database
        $userActivities = Activity::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit * 2) // Fetch extra for rotation
            ->get([
                'type',
                'title',
                'value',
                'created_at',
                'event_type',
                'icon',
                'link',
            ])
            ->map(function ($activity) {
                return [
                    'type' => $activity->type,
                    'title' => $activity->title,
                    'value' => $activity->value ?? 'Update',
                    'time' => $activity->created_at->toISOString(),
                    'event_type' => $activity->event_type,
                    'icon' => $activity->icon,
                    'link' => $activity->link,
                ];
            });

        $total = $userActivities->count();
        $offset = $total > 0 ? $windowKey % $total : 0;

        $rotated = $total > 0
            ? $userActivities->slice($offset)->concat($userActivities->take($offset))->values()
            : collect();

        Cache::put($cacheKey, $windowKey, now()->addMinutes(5));

        return response()->json([
            'data' => $rotated->take($limit)->values()->all(),
            'meta' => [
                'limit' => $limit,
                'total' => $total,
                'has_new_data' => $hasNewData,
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    public function topOffers(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->integer('per_page', 10)));
        $user = $request->user();
        
        $paginator = Offer::query()
            ->where('user_id', $user->id)
            ->orderByDesc('earnings')
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

    public function export(Request $request): JsonResponse
    {
        $data = $request->validate([
            'range' => ['required', 'in:24h,7d,30d,90d'],
            'metrics' => ['nullable', 'array'],
        ]);
        
        $jobId = uniqid('export_');
        
        return response()->json([
            'job_id' => $jobId,
        ], 202);
    }
}
