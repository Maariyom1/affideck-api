<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'value',
        'icon',
        'link',
        'event_type',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log an activity for a user.
     */
    public static function log(int $userId, string $type, string $title, ?string $value = null, ?string $icon = null, ?string $link = null, ?string $eventType = null, ?array $metadata = null): self
    {
        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'value' => $value,
            'icon' => $icon,
            'link' => $link,
            'event_type' => $eventType ?? $type,
            'metadata' => $metadata,
        ]);
    }
}

