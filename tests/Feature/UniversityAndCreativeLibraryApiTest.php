<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\CreativeAsset;
use App\Models\UniversityCourse;
use App\Models\UniversityEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UniversityAndCreativeLibraryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_university_categories_are_public(): void
    {
        $response = $this->getJson('/api/university/categories');

        $response->assertOk()->assertJsonPath('data.0', 'Google Ads');
    }

    public function test_university_course_listing_filters_and_paginates(): void
    {
        $user = User::factory()->create();
        $tokens = ApiToken::issuePair($user);

        UniversityCourse::query()->create([
            'title' => 'Meta Ads — Full Setup',
            'description' => 'Pixel, audiences, and your first profitable campaign.',
            'category' => 'Meta Ads',
            'duration_minutes' => 1438,
            'instructor' => 'John Doe',
            'thumbnail_url' => 'https://example.com/meta.jpg',
            'is_featured' => true,
            'content' => '# Meta Ads',
            'video_url' => 'https://vimeo.com/video-id',
            'prerequisites' => ['Meta Ads Basics'],
        ]);

        UniversityCourse::query()->create([
            'title' => 'TikTok Walkthrough',
            'description' => 'Business Center, testing, and scaling winners.',
            'category' => 'TikTok Paid',
            'duration_minutes' => 1122,
            'instructor' => 'Jane Smith',
            'thumbnail_url' => 'https://example.com/tiktok.jpg',
            'is_featured' => false,
            'content' => '# TikTok',
            'video_url' => 'https://youtube.com/watch?v=video-id',
            'prerequisites' => ['TikTok Ads Basics'],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->getJson('/api/university/courses?category=Meta%20Ads&q=Full%20Setup&per_page=1');

        $response->assertOk();
        $response->assertJsonPath('meta.per_page', 1);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.title', 'Meta Ads — Full Setup');
    }

    public function test_university_detail_enroll_and_progress_flow_works(): void
    {
        $user = User::factory()->create();
        $tokens = ApiToken::issuePair($user);

        $course = UniversityCourse::query()->create([
            'title' => 'Meta Ads — Full Setup',
            'description' => 'Pixel, audiences, and your first profitable campaign.',
            'category' => 'Meta Ads',
            'duration_minutes' => 1438,
            'instructor' => 'John Doe',
            'thumbnail_url' => 'https://example.com/meta.jpg',
            'is_featured' => true,
            'content' => '# Module 1: Pixel Setup',
            'video_url' => 'https://vimeo.com/video-id',
            'prerequisites' => [],
        ]);

        $detail = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->getJson('/api/university/courses/'.$course->id);

        $detail->assertOk()
            ->assertJsonPath('data.id', $course->id)
            ->assertJsonPath('data.is_enrolled', false)
            ->assertJsonPath('data.progress_percent', 0);

        $enroll = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->postJson('/api/university/courses/'.$course->id.'/enroll', []);

        $enroll->assertOk()
            ->assertJsonPath('message', 'Enrolled successfully');

        $this->assertDatabaseHas('university_enrollments', [
            'user_id' => $user->id,
            'university_course_id' => $course->id,
        ]);

        $progress = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->getJson('/api/university/progress');

        $progress->assertOk()
            ->assertJsonPath('data.total_enrolled', 1)
            ->assertJsonPath('data.total_completed', 0)
            ->assertJsonPath('data.in_progress', 1)
            ->assertJsonPath('data.enrollments.0.course_id', $course->id);
    }

    public function test_admin_can_create_update_and_delete_university_courses(): void
    {
        $admin = User::factory()->create(['email' => 'admin@affideck.com']);
        $tokens = ApiToken::issuePair($admin);

        $create = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->postJson('/api/university/courses', [
                'title' => 'New Course Title',
                'description' => 'Course description',
                'category' => 'Google Ads',
                'duration_minutes' => 1200,
                'instructor' => 'Instructor Name',
                'thumbnail_url' => 'https://example.com/course.jpg',
                'is_featured' => false,
                'content' => 'Course content in markdown',
                'video_url' => 'https://vimeo.com/video-id',
                'prerequisites' => ['Course 1', 'Course 2'],
            ]);

        $create->assertCreated()->assertJsonPath('data.title', 'New Course Title');
        $courseId = $create->json('data.id');

        $update = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->patchJson('/api/university/courses/'.$courseId, [
                'title' => 'Updated Title',
            ]);

        $update->assertOk()->assertJsonPath('data.title', 'Updated Title');

        $delete = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->deleteJson('/api/university/courses/'.$courseId);

        $delete->assertOk()->assertJsonPath('message', 'Course deleted successfully');
    }

    public function test_non_admin_cannot_create_university_courses(): void
    {
        $user = User::factory()->create();
        $tokens = ApiToken::issuePair($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->postJson('/api/university/courses', [
                'title' => 'Blocked Course',
                'description' => 'Blocked',
                'category' => 'Meta Ads',
                'duration_minutes' => 120,
                'instructor' => 'Teacher',
                'content' => 'Content',
            ]);

        $response->assertStatus(403);
    }

    public function test_creative_library_tags_assets_upload_and_download_flow_works(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $tokens = ApiToken::issuePair($user);

        $existing = CreativeAsset::query()->create([
            'created_by_id' => $user->id,
            'name' => 'Meta Ad Thumbnail v1',
            'description' => 'High-performing thumbnail for Meta ads',
            'file_path' => 'creative-assets/meta-thumbnail-v1.jpg',
            'file_url' => 'https://example.com/meta-thumbnail-v1.jpg',
            'preview_url' => 'https://example.com/meta-thumbnail-v1.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'file_size_kb' => 245,
            'tags' => ['meta', 'thumbnail', 'high-ctr'],
            'status' => 'active',
            'dimensions' => '1200x628',
            'download_count' => 12,
        ]);

        $tags = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->getJson('/api/creative-library/tags');

        $tags->assertOk()->assertJsonPath('data.0', 'meta');

        $listing = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->getJson('/api/creative-library/assets?tag=meta&q=thumbnail');

        $listing->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $existing->id);

        $detail = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->getJson('/api/creative-library/assets/'.$existing->id);

        $detail->assertOk()->assertJsonPath('data.id', $existing->id);

        $upload = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->post('/api/creative-library/assets', [
                'file' => UploadedFile::fake()->create('thumbnail.jpg', 256, 'image/jpeg'),
                'name' => 'Uploaded Thumbnail',
                'description' => 'Uploaded asset',
                'tags' => ['meta', 'thumbnail'],
            ]);

        $upload->assertCreated()->assertJsonPath('data.name', 'Uploaded Thumbnail');
        $uploadedId = $upload->json('data.id');

        $update = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->patchJson('/api/creative-library/assets/'.$uploadedId, [
                'name' => 'Updated Asset',
                'tags' => ['meta', 'thumbnail', 'v2'],
            ]);

        $update->assertOk()->assertJsonPath('data.name', 'Updated Asset');

        $download = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->getJson('/api/creative-library/assets/'.$uploadedId.'/download');

        $download->assertOk()->assertJsonStructure(['download_url']);

        $delete = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->deleteJson('/api/creative-library/assets/'.$uploadedId);

        $delete->assertOk()->assertJsonPath('message', 'Asset deleted successfully');
    }

    public function test_asset_creator_or_admin_is_required_for_asset_updates(): void
    {
        $owner = User::factory()->create();
        $ownerTokens = ApiToken::issuePair($owner);
        $other = User::factory()->create();
        $otherTokens = ApiToken::issuePair($other);

        $asset = CreativeAsset::query()->create([
            'created_by_id' => $owner->id,
            'name' => 'Owner Asset',
            'description' => 'Owned asset',
            'file_path' => 'creative-assets/owner.jpg',
            'file_url' => 'https://example.com/owner.jpg',
            'preview_url' => 'https://example.com/owner.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'file_size_kb' => 100,
            'tags' => ['meta'],
            'status' => 'active',
            'dimensions' => '1200x628',
            'download_count' => 0,
        ]);

        $blocked = $this->withHeader('Authorization', 'Bearer '.$otherTokens['access_token'])
            ->patchJson('/api/creative-library/assets/'.$asset->id, [
                'name' => 'Intrusion',
            ]);

        $blocked->assertStatus(403);

        $allowed = $this->withHeader('Authorization', 'Bearer '.$ownerTokens['access_token'])
            ->patchJson('/api/creative-library/assets/'.$asset->id, [
                'name' => 'Owner Update',
            ]);

        $allowed->assertOk()->assertJsonPath('data.name', 'Owner Update');
    }
}
