<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CapitalApplication;
use Illuminate\Http\Request;

class CapitalController extends Controller
{
    public function eligibility(Request $request)
    {
        $user = $request->user();
        $eligible = $user->created_at->diffInDays(now()) > 7;
        return response()->json(['data' => ['eligible' => $eligible]]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['amount' => 'required|numeric|min:100']);
        $data['user_id'] = $request->user()->id;
        $app = CapitalApplication::create($data);
        return response()->json(['data' => $app], 201);
    }

    public function index(Request $request)
    {
        $items = CapitalApplication::where('user_id', $request->user()->id)->paginate($request->input('per_page', 15));
        return response()->json(['data' => $items, 'meta' => $this->pageMeta($items)]);
    }

    public function show(Request $request, $id)
    {
        $app = CapitalApplication::where('user_id', $request->user()->id)->findOrFail($id);
        return response()->json(['data' => $app]);
    }

    public function update(Request $request, $id)
    {
        $app = CapitalApplication::where('user_id', $request->user()->id)->findOrFail($id);
        $this->authorize('update', $app);
        $app->update($request->only(['status', 'notes']));
        return response()->json(['data' => $app]);
    }

    private function pageMeta($paginator)
    {
        return ['current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total(), 'last_page' => $paginator->lastPage()];
    }
}
