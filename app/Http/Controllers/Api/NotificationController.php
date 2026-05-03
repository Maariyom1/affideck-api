<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = max(1, min(100, (int) $request->integer('per_page', 15)));
        $unreadOnly = $request->boolean('unread_only');

        $query = $user->notifications()->latest();

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => NotificationResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    public function update(Request $request, string $notificationId): JsonResponse
    {
        $data = $request->validate([
            'read' => ['required', 'boolean'],
        ]);

        $notification = $this->findUserNotification($request, $notificationId);

        if ($data['read']) {
            $notification->markAsRead();
        } else {
            $notification->forceFill(['read_at' => null])->save();
        }

        return response()->json([
            'data' => new NotificationResource($notification->refresh()),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }

    public function stream(Request $request): StreamedResponse
    {
        return response()->stream(function () use ($request): void {
            echo 'event: notification';
            echo PHP_EOL;
            echo 'data: '.json_encode([
                'unread_count' => $request->user()->unreadNotifications()->count(),
                'ts' => now()->toISOString(),
            ]);
            echo PHP_EOL.PHP_EOL;
            @ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function findUserNotification(Request $request, string $notificationId): DatabaseNotification
    {
        return $request->user()->notifications()->where('id', $notificationId)->firstOrFail();
    }
}