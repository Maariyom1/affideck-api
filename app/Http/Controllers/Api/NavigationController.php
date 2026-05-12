<?php

namespace App\Http\Controllers\Api;

use App\Models\Activity;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class NavigationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdmin = $user?->isAdmin() ?? false;

        return response()->json([
            'data' => [
                [
                    'label' => 'Dashboard',
                    'href' => '/dashboard',
                    'permission' => 'viewDashboard',
                ],
                [
                    'label' => 'Offers',
                    'href' => '/offers',
                    'permission' => 'viewOffers',
                ],
                [
                    'label' => 'Marketplace',
                    'href' => '/marketplace',
                    'permission' => 'viewMarketplace',
                ],
                [
                    'label' => 'Communities',
                    'href' => '/communities',
                    'permission' => 'viewCommunities',
                ],
                [
                    'label' => 'Capital',
                    'href' => '/capital',
                    'permission' => 'viewCapital',
                ],
                [
                    'label' => 'Referrals',
                    'href' => '/referrals',
                    'permission' => 'viewReferrals',
                ],
                [
                    'label' => 'Settings',
                    'href' => '/settings',
                    'permission' => 'viewSettings',
                ],
                ...($isAdmin ? [[
                    'label' => 'Admin',
                    'href' => '/admin',
                    'permission' => 'adminPanel',
                ]] : []),
            ],
        ]);
    }

    public function liveFeed(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->integer('per_page', 8)));
        $page = max(1, (int) $request->integer('page', 1));

        // Query real activities from the database ordered by newest first
        $allActivities = Activity::query()
            ->orderByDesc('created_at')
            ->get(['type', 'title', 'value', 'created_at', 'icon', 'link'])
            ->map(function ($activity) {
                return [
                    'type' => $activity->type,
                    'text' => $activity->title,
                    'link' => $activity->link,
                    'ts' => $activity->created_at->toISOString(),
                    'icon' => $activity->icon,
                ];
            });

        $total = $allActivities->count();
        $lastPage = max(1, (int) ceil($total / $perPage));

        // Rotate the feed window every 10 seconds so polling clients receive a fresh slice.
        $offset = $total > 0 ? (int) (intdiv(now()->unix(), 10) % $total) : 0;
        $rotatedItems = $total > 0
            ? $allActivities->slice($offset)->concat($allActivities->take($offset))->values()
            : collect();

        $pageItems = $rotatedItems->forPage($page, $perPage)->values();

        $windowKey = intdiv(now()->unix(), 10);
        $cacheKey = 'live-feed:last-window-key';
        $previousWindowKey = Cache::get($cacheKey);
        $hasNewData = $previousWindowKey === null || (int) $previousWindowKey !== $windowKey;
        
        Cache::put($cacheKey, $windowKey, now()->addMinutes(5));

        return response()->json([
            'data' => $pageItems->all(),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'has_new_data' => $page === 1 && $hasNewData,
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * @return array<int, array{type: string, text: string, link: string|null, ts: string}>
     */
    private function liveFeedItems(): array
    {
        // Kept for backward compatibility, but live-feed now queries activities table
        return [];
    }
}