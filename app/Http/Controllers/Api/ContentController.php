<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\CmsPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ContentController extends Controller
{
    public function page(string $slug): JsonResponse
    {
        $page = CmsPage::where('slug', $slug)->where('published', true)->firstOrFail();
        
        return response()->json([
            'data' => [
                'title' => $page->title,
                'content' => $page->content,
                'slug' => $page->slug,
            ],
        ]);
    }

    public function blog(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->integer('per_page', 15)));
        
        $paginator = BlogPost::query()
            ->where('published', true)
            ->latest('published_at')
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

    public function blogPost(string $slug): JsonResponse
    {
        $post = BlogPost::where('slug', $slug)->where('published', true)->firstOrFail();
        
        return response()->json([
            'data' => $post,
        ]);
    }

    public function contact(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'message' => ['required', 'string', 'min:10'],
        ]);
        
        return response()->json([
            'message' => 'Your message has been received. We will get back to you soon.',
        ], 201);
    }
}
