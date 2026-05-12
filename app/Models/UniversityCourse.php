<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class UniversityCourse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'title',
        'description',
        'category',
        'duration_minutes',
        'instructor',
        'thumbnail_url',
        'is_featured',
        'content',
        'video_url',
        'prerequisites',
        'created_by_id',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'is_featured' => 'boolean',
        'prerequisites' => 'array',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::creating(function (self $course): void {
            if (! is_string($course->id) || $course->id === '') {
                $course->id = (string) Str::uuid();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(UniversityEnrollment::class);
    }
}
