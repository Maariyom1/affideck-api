<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\CommunityPost;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    public function index(Request $request)
    {
        $items = Community::paginate($request->input('per_page', 15));
        return response()->json(['data' => $items, 'meta' => $this->pageMeta($items)]);
    }

    public function show($id)
    {
        $c = Community::with('owner')->findOrFail($id);
        return response()->json(['data' => $c]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string', 'slug' => 'required|string|unique:communities,slug', 'description' => 'nullable|string', 'private' => 'boolean']);
        $data['owner_id'] = $request->user()->id;
        $community = Community::create($data);
        CommunityMember::create(['community_id' => $community->id, 'user_id' => $request->user()->id, 'role' => 'admin']);
        return response()->json(['data' => $community], 201);
    }

    public function update(Request $request, $id)
    {
        $community = Community::findOrFail($id);
        $this->authorize('update', $community);
        $community->update($request->only(['name', 'description', 'private']));
        return response()->json(['data' => $community]);
    }

    public function join(Request $request, $id)
    {
        $community = Community::findOrFail($id);
        CommunityMember::firstOrCreate(['community_id' => $community->id, 'user_id' => $request->user()->id], ['role' => 'member']);
        return response()->json(['message' => 'joined'], 201);
    }

    public function setMemberRole(Request $request, $id, $userId)
    {
        $request->validate(['role' => 'required|string']);
        $member = CommunityMember::where('community_id', $id)->where('user_id', $userId)->firstOrFail();
        $member->role = $request->input('role');
        $member->save();
        return response()->json(['data' => $member]);
    }

    public function posts(Request $request, $id)
    {
        $posts = CommunityPost::where('community_id', $id)->paginate($request->input('per_page', 15));
        return response()->json(['data' => $posts, 'meta' => $this->pageMeta($posts)]);
    }

    public function storePost(Request $request, $id)
    {
        $data = $request->validate(['title' => 'nullable|string', 'body' => 'required|string']);
        $data['community_id'] = $id;
        $data['author_id'] = $request->user()->id;
        $post = CommunityPost::create($data);
        return response()->json(['data' => $post], 201);
    }

    private function pageMeta($paginator)
    {
        return ['current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total(), 'last_page' => $paginator->lastPage()];
    }
}
