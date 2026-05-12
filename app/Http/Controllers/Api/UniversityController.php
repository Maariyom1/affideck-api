<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UniversityCourse;
use App\Models\UniversityEnrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UniversityController extends Controller
{
    private const CATEGORIES = [
        'Google Ads',
        'Meta Ads',
        'TikTok Organic',
        'TikTok Paid',
        'Instagram',
        'Social SEO',
        'AI',
        'Email Marketing',
    ];

    public function categories(): JsonResponse
    {
        return response()->json(['data' => self::CATEGORIES]);
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->integer('per_page', 12)));
        $category = trim((string) $request->query('category', ''));
        $search = trim((string) $request->query('q', ''));

        $query = UniversityCourse::query()->whereNull('deleted_at');

        if ($category !== '') {
            $query->where('category', $category);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        $paginator = $query->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())->map(fn (UniversityCourse $course) => $this->courseSummary($course))->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $course = UniversityCourse::query()->whereKey($id)->firstOrFail();
        $enrollment = UniversityEnrollment::query()
            ->where('user_id', $request->user()->id)
            ->where('university_course_id', $course->id)
            ->first();

        return response()->json([
            'data' => $this->courseDetail($course, $enrollment),
        ]);
    }

    public function enroll(Request $request, string $id): JsonResponse
    {
        $course = UniversityCourse::query()->whereKey($id)->firstOrFail();

        $existing = UniversityEnrollment::query()
            ->where('user_id', $request->user()->id)
            ->where('university_course_id', $course->id)
            ->first();

        if ($existing !== null) {
            return response()->json(['message' => 'Already enrolled in this course.'], 409);
        }

        $missingPrerequisites = $this->missingPrerequisites($request->user()->id, $course->prerequisites ?? []);

        if ($missingPrerequisites !== []) {
            return response()->json([
                'message' => 'Not eligible. Missing prerequisites.',
                'errors' => ['prerequisites' => $missingPrerequisites],
            ], 403);
        }

        $enrollment = UniversityEnrollment::query()->create([
            'user_id' => $request->user()->id,
            'university_course_id' => $course->id,
            'progress_percent' => 0,
            'lessons_completed' => 0,
            'lessons_total' => $this->estimateLessonsTotal($course),
            'started_at' => now(),
            'last_accessed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Enrolled successfully',
            'data' => [
                'enrollment_id' => $enrollment->id,
                'started_at' => optional($enrollment->started_at)->toISOString(),
            ],
        ]);
    }

    public function progress(Request $request): JsonResponse
    {
        $enrollments = UniversityEnrollment::query()
            ->with('course')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_accessed_at')
            ->get();

        $totalEnrolled = $enrollments->count();
        $totalCompleted = $enrollments->where('progress_percent', 100)->count();

        return response()->json([
            'data' => [
                'total_enrolled' => $totalEnrolled,
                'total_completed' => $totalCompleted,
                'in_progress' => max(0, $totalEnrolled - $totalCompleted),
                'enrollments' => $enrollments->map(function (UniversityEnrollment $enrollment): array {
                    return [
                        'course_id' => $enrollment->course?->id,
                        'course_title' => $enrollment->course?->title,
                        'progress_percent' => $enrollment->progress_percent,
                        'last_accessed' => optional($enrollment->last_accessed_at)->toISOString(),
                    ];
                })->values()->all(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateCourse($request);

        $course = UniversityCourse::query()->create([
            ...$data,
            'created_by_id' => $request->user()->id,
        ]);

        return response()->json(['data' => $this->courseSummary($course)], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $course = UniversityCourse::query()->whereKey($id)->firstOrFail();
        $data = $this->validateCourse($request, false);

        $course->fill($data)->save();

        return response()->json(['data' => $this->courseSummary($course->refresh())]);
    }

    public function destroy(string $id): JsonResponse
    {
        $course = UniversityCourse::query()->whereKey($id)->firstOrFail();
        $course->delete();

        return response()->json(['message' => 'Course deleted successfully']);
    }

    private function validateCourse(Request $request, bool $creating = true): array
    {
        $rules = [
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => [$creating ? 'required' : 'sometimes', 'string', 'max:1000'],
            'category' => [$creating ? 'required' : 'sometimes', 'string', 'max:100', 'in:'.implode(',', self::CATEGORIES)],
            'duration_minutes' => [$creating ? 'required' : 'sometimes', 'integer', 'min:1'],
            'instructor' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'thumbnail_url' => ['nullable', 'string', 'url', 'max:2048'],
            'is_featured' => ['sometimes', 'boolean'],
            'content' => [$creating ? 'required' : 'sometimes', 'string'],
            'video_url' => ['nullable', 'string', 'url', 'max:2048'],
            'prerequisites' => ['nullable'],
        ];

        $data = $request->validate($rules);

        if (array_key_exists('prerequisites', $data)) {
            $data['prerequisites'] = $this->normalizeTags($data['prerequisites']);
        }

        return $data;
    }

    private function courseSummary(UniversityCourse $course): array
    {
        return [
            'id' => $course->id,
            'title' => $course->title,
            'description' => $course->description,
            'category' => $course->category,
            'duration_minutes' => (int) $course->duration_minutes,
            'instructor' => $course->instructor,
            'thumbnail_url' => $course->thumbnail_url,
            'is_featured' => (bool) $course->is_featured,
            'created_at' => optional($course->created_at)->toISOString(),
            'updated_at' => optional($course->updated_at)->toISOString(),
        ];
    }

    private function courseDetail(UniversityCourse $course, ?UniversityEnrollment $enrollment = null): array
    {
        $summary = $this->courseSummary($course);

        return [
            ...$summary,
            'content' => $course->content,
            'video_url' => $course->video_url,
            'is_enrolled' => $enrollment !== null,
            'progress_percent' => $enrollment?->progress_percent ?? 0,
            'lessons_completed' => $enrollment?->lessons_completed ?? 0,
            'lessons_total' => $enrollment?->lessons_total ?? $this->estimateLessonsTotal($course),
            'prerequisites' => $course->prerequisites ?? [],
        ];
    }

    private function estimateLessonsTotal(UniversityCourse $course): int
    {
        return max(1, (int) ceil(((int) $course->duration_minutes) / 45));
    }

    private function missingPrerequisites(int $userId, array $prerequisites): array
    {
        if ($prerequisites === []) {
            return [];
        }

        $completedCourseTitles = UniversityEnrollment::query()
            ->with('course')
            ->where('user_id', $userId)
            ->where('progress_percent', 100)
            ->get()
            ->map(fn (UniversityEnrollment $enrollment) => $enrollment->course?->title)
            ->filter()
            ->values()
            ->all();

        return array_values(array_diff($prerequisites, $completedCourseTitles));
    }

    private function normalizeTags(mixed $tags): array
    {
        if (is_array($tags)) {
            $values = $tags;
        } elseif (is_string($tags)) {
            $decoded = json_decode($tags, true);
            if (is_array($decoded)) {
                $values = $decoded;
            } else {
                $values = array_map('trim', explode(',', $tags));
            }
        } else {
            $values = [];
        }

        $values = array_values(array_unique(array_filter(array_map(static fn ($value) => trim((string) $value), $values))));

        if (count($values) > 10) {
            throw ValidationException::withMessages([
                'prerequisites' => ['The prerequisites may not contain more than 10 items.'],
            ]);
        }

        return $values;
    }
}
