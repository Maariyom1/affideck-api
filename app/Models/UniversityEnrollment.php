<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UniversityEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'user_id',
        'university_course_id',
        'progress_percent',
        'lessons_completed',
        'lessons_total',
        'started_at',
        'last_accessed_at',
        'completed_at',
    ];

    protected $casts = [
        'progress_percent' => 'integer',
        'lessons_completed' => 'integer',
        'lessons_total' => 'integer',
        'started_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::creating(function (self $enrollment): void {
            if (! is_string($enrollment->id) || $enrollment->id === '') {
                $enrollment->id = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(UniversityCourse::class, 'university_course_id');
    }
}
