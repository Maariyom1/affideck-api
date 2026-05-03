<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function show(Request $request)
    {
        $s = Setting::firstOrCreate(['user_id' => $request->user()->id]);
        return response()->json(['data' => $s]);
    }

    public function updateAccount(Request $request)
    {
        $data = $request->validate(['preferences' => 'nullable|array']);
        $s = Setting::firstOrCreate(['user_id' => $request->user()->id]);
        $s->preferences = $data['preferences'] ?? $s->preferences;
        $s->save();
        return response()->json(['data' => $s]);
    }

    public function updatePaymentMethods(Request $request)
    {
        $data = $request->validate(['payment_methods' => 'nullable|array']);
        $s = Setting::firstOrCreate(['user_id' => $request->user()->id]);
        $s->payment_methods = $data['payment_methods'] ?? $s->payment_methods;
        $s->save();
        return response()->json(['data' => $s]);
    }

    public function payouts(Request $request)
    {
        $s = Setting::firstOrCreate(['user_id' => $request->user()->id]);
        return response()->json(['data' => ['payouts' => []]]);
    }

    public function verifyIdentity(Request $request)
    {
        $svc = new \App\Services\KycService();
        $result = $svc->startVerification($request->all());

        $s = Setting::firstOrCreate(['user_id' => $request->user()->id]);
        $prefs = $s->preferences ?? [];
        $prefs['kyc'] = $result;
        $s->preferences = $prefs;
        $s->save();

        return response()->json(['data' => $result], 202);
    }
}
