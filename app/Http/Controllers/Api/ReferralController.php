<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\ReferralConversion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReferralController extends Controller
{
    public function index(Request $request)
    {
        $refs = Referral::where('user_id', $request->user()->id)->get();
        return response()->json(['data' => $refs]);
    }

    public function shareLink(Request $request)
    {
        $ref = Referral::firstOrCreate(['user_id' => $request->user()->id], ['code' => Str::upper(Str::random(8))]);
        return response()->json(['data' => ['code' => $ref->code, 'link' => url('/?ref='.$ref->code)]]);
    }

    public function share(Request $request)
    {
        $data = $request->validate(['channel' => 'nullable|string']);
        return response()->json(['message' => 'shared']);
    }

    public function conversions(Request $request)
    {
        $rows = ReferralConversion::whereHas('referral', function ($q) use ($request) { $q->where('user_id', $request->user()->id); })->get();
        return response()->json(['data' => $rows]);
    }

    public function commissions(Request $request)
    {
        $query = ReferralConversion::whereHas('referral', function ($q) use ($request) { 
            $q->where('user_id', $request->user()->id); 
        });
        
        $total = $query->sum('commission');
        $pending = $query->where('status', 'pending')->sum('commission');
        $paid = $query->where('status', 'paid')->sum('commission');
        
        return response()->json([
            'data' => [
                'total_commissions' => $total,
                'pending' => $pending,
                'paid' => $paid,
            ]
        ]);
    }
}
