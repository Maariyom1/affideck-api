<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
