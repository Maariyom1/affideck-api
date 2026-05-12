<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreativeAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreativeLibraryController extends Controller
{
    private const TAGS = [
        'meta',
        'thumbnail',
        'high-ctr',
        'facebook',
        'instagram',
        'tiktok',
        'landing-page',
        'email',
        'funnel',
    ];

    public function tags(): JsonResponse
    {
        return response()->json(['data' => self::TAGS]);
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->integer('per_page', 20)));
        $tag = trim((string) $request->query('tag', ''));
        $search = trim((string) $request->query('q', ''));

        $query = CreativeAsset::query()->whereNull('deleted_at');

        if ($tag !== '') {
            $query->whereJsonContains('tags', $tag);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        $paginator = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())->map(fn (CreativeAsset $asset) => $this->assetSummary($asset))->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $asset = CreativeAsset::query()->with('creator')->whereKey($id)->firstOrFail();
        $similarAssets = CreativeAsset::query()
            ->with('creator')
            ->whereNull('deleted_at')
            ->where('id', '!=', $asset->id)
            ->orderByDesc('created_at')
            ->get()
            ->filter(function (CreativeAsset $candidate) use ($asset): bool {
                if ($candidate->type === $asset->type) {
                    return true;
                }

                return count(array_intersect($candidate->tags ?? [], $asset->tags ?? [])) > 0;
            })
            ->take(4)
            ->values()
            ->map(fn (CreativeAsset $candidate) => $this->assetSummary($candidate))
            ->all();

        return response()->json([
            'data' => [
                ...$this->assetSummary($asset->loadMissing('creator')),
                'dimensions' => $asset->dimensions,
                'similar_assets' => $similarAssets,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable'],
        ]);

        $file = $request->file('file');
        $mimeType = (string) $file->getMimeType();
        $type = $this->resolveType($mimeType);
        $path = $file->store('creative-assets', 'public');
        $url = Storage::disk('public')->url($path);
        $dimensions = $this->resolveDimensions($file->getRealPath(), $type);

        $asset = CreativeAsset::query()->create([
            'created_by_id' => $request->user()->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'file_path' => $path,
            'file_url' => $url,
            'preview_url' => $url,
            'type' => $type,
            'mime_type' => $mimeType,
            'file_size_kb' => (int) ceil($file->getSize() / 1024),
            'tags' => $this->normalizeTags($data['tags'] ?? []),
            'status' => 'active',
            'dimensions' => $dimensions,
            'download_count' => 0,
        ]);

        return response()->json(['data' => $this->assetSummary($asset->loadMissing('creator'))], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $asset = CreativeAsset::query()->whereKey($id)->firstOrFail();

        $this->authorizeAsset($request, $asset);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'tags' => ['sometimes', 'nullable'],
        ]);

        if (array_key_exists('tags', $data)) {
            $data['tags'] = $this->normalizeTags($data['tags']);
        }

        $asset->fill($data)->save();

        return response()->json(['data' => $this->assetSummary($asset->loadMissing('creator'))]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $asset = CreativeAsset::query()->whereKey($id)->firstOrFail();

        $this->authorizeAsset($request, $asset);

        if ($asset->file_path && Storage::disk('public')->exists($asset->file_path)) {
            Storage::disk('public')->delete($asset->file_path);
        }

        $asset->delete();

        return response()->json(['message' => 'Asset deleted successfully']);
    }

    public function download(Request $request, string $id): JsonResponse
    {
        $asset = CreativeAsset::query()->whereKey($id)->firstOrFail();
        $asset->increment('download_count');

        return response()->json([
            'download_url' => $asset->file_url,
        ]);
    }

    private function assetSummary(CreativeAsset $asset): array
    {
        return [
            'id' => $asset->id,
            'name' => $asset->name,
            'description' => $asset->description,
            'file_url' => $asset->file_url,
            'preview_url' => $asset->preview_url,
            'type' => $asset->type,
            'mime_type' => $asset->mime_type,
            'file_size_kb' => (int) $asset->file_size_kb,
            'tags' => $asset->tags ?? [],
            'status' => $asset->status,
            'created_at' => optional($asset->created_at)->toISOString(),
            'created_by' => [
                'id' => $asset->creator?->id,
                'name' => $asset->creator?->name,
            ],
            'download_count' => (int) $asset->download_count,
        ];
    }

    private function normalizeTags(mixed $tags): array
    {
        if (is_array($tags)) {
            $values = $tags;
        } elseif (is_string($tags)) {
            $decoded = json_decode($tags, true);
            $values = is_array($decoded) ? $decoded : array_map('trim', explode(',', $tags));
        } else {
            $values = [];
        }

        $values = array_values(array_unique(array_filter(array_map(static fn ($value) => trim((string) $value), $values))));

        if (count($values) > 10) {
            throw ValidationException::withMessages([
                'tags' => ['The tags may not contain more than 10 items.'],
            ]);
        }

        return $values;
    }

    private function resolveType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        return 'document';
    }

    private function resolveDimensions(string $path, string $type): ?string
    {
        if ($type !== 'image' || ! is_file($path)) {
            return null;
        }

        $size = @getimagesize($path);

        if (! is_array($size) || ! isset($size[0], $size[1])) {
            return null;
        }

        return $size[0].'x'.$size[1];
    }

    private function authorizeAsset(Request $request, CreativeAsset $asset): void
    {
        if ($request->user()->id !== $asset->created_by_id && ! $request->user()->isAdmin()) {
            abort(403, 'Forbidden.');
        }
    }
}
