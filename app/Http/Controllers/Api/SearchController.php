<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->string('q');
        $type = $request->string('type', 'all');
        
        if (empty($query)) {
            return response()->json(['data' => []]);
        }
        
        $results = [];
        
        return response()->json([
            'data' => $results,
        ]);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->string('q');
        
        if (empty($query)) {
            return response()->json(['data' => []]);
        }
        
        $suggestions = [
            'Technology',
            'Marketing',
            'Business',
        ];
        
        return response()->json([
            'data' => $suggestions,
        ]);
    }
}
