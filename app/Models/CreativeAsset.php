<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CreativeAsset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'created_by_id',
        'name',
        'description',
        'file_path',
        'file_url',
        'preview_url',
        'type',
        'mime_type',
        'file_size_kb',
        'tags',
        'status',
        'dimensions',
        'download_count',
    ];

    protected $casts = [
        'tags' => 'array',
        'file_size_kb' => 'integer',
        'download_count' => 'integer',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::creating(function (self $asset): void {
            if (! is_string($asset->id) || $asset->id === '') {
                $asset->id = (string) Str::uuid();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
