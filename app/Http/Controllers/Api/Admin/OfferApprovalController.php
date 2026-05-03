<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\OfferApproval;
use Illuminate\Http\Request;

class OfferApprovalController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $approvals = OfferApproval::orderBy('requested_at', 'desc')->paginate(15);

        return response()->json($approvals);
    }

    public function approve(Request $request, $approvalId)
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $approval = OfferApproval::find($approvalId);

        if ($approval === null) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $approval->status = 'approved';
        $approval->reviewed_by = $user->getKey();
        $approval->reviewed_at = now();
        $approval->save();

        $offer = Offer::find($approval->offer_id);

        if ($offer !== null) {
            $offer->status = 'published';
            $offer->save();
        }

        return response()->json(['data' => $approval]);
    }

    public function deny(Request $request, $approvalId)
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $approval = OfferApproval::find($approvalId);

        if ($approval === null) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $approval->status = 'denied';
        $approval->reviewed_by = $user->getKey();
        $approval->reviewed_at = now();
        $approval->save();

        $offer = Offer::find($approval->offer_id);

        if ($offer !== null) {
            $offer->status = 'archived';
            $offer->save();
        }

        return response()->json(['data' => $approval]);
    }
}
