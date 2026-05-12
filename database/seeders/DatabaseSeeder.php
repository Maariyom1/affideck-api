<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\CreativeAsset;
use App\Models\UniversityCourse;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Developer test user
        User::query()->updateOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
        ]);

        // Seed admin user for local/dev testing
        $admin = User::query()->updateOrCreate([
            'email' => 'admin@affideck.com',
        ], [
            'name' => 'Admin User',
            'password' => 'admin',
        ]);

        UniversityCourse::query()->firstOrCreate([
            'title' => 'Meta Ads — Full Setup',
        ], [
            'id' => Str::uuid(),
            'created_by_id' => $admin->id,
            'description' => 'Pixel, audiences, and your first profitable campaign.',
            'category' => 'Meta Ads',
            'duration_minutes' => 1438,
            'instructor' => 'John Doe',
            'thumbnail_url' => 'https://placehold.co/1200x628/png',
            'is_featured' => true,
            'content' => '# Module 1: Pixel Setup\n\nFull markdown content explaining course content, lessons, etc.',
            'video_url' => 'https://vimeo.com/video-id',
            'prerequisites' => ['Meta Ads Basics', 'Facebook Business Manager Fundamentals'],
        ]);

        UniversityCourse::query()->firstOrCreate([
            'title' => 'TikTok Walkthrough',
        ], [
            'id' => Str::uuid(),
            'created_by_id' => $admin->id,
            'description' => 'Business Center, testing, and scaling winners.',
            'category' => 'TikTok Paid',
            'duration_minutes' => 1122,
            'instructor' => 'Jane Smith',
            'thumbnail_url' => 'https://placehold.co/1200x628/png?text=TikTok',
            'is_featured' => false,
            'content' => '# TikTok Walkthrough\n\nA practical walkthrough for launching and scaling TikTok ads.',
            'video_url' => 'https://youtube.com/watch?v=video-id',
            'prerequisites' => ['TikTok Ads Basics'],
        ]);

        CreativeAsset::query()->firstOrCreate([
            'name' => 'Meta Ad Thumbnail v1',
        ], [
            'id' => Str::uuid(),
            'created_by_id' => $admin->id,
            'description' => 'High-performing thumbnail for Meta ads',
            'file_path' => 'creative-assets/meta-thumbnail-v1.jpg',
            'file_url' => 'https://placehold.co/1200x628/jpg',
            'preview_url' => 'https://placehold.co/1200x628/jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'file_size_kb' => 245,
            'tags' => ['meta', 'thumbnail', 'high-ctr'],
            'status' => 'active',
            'dimensions' => '1200x628',
            'download_count' => 12,
        ]);

        CreativeAsset::query()->firstOrCreate([
            'name' => 'Facebook Lead Form Video',
        ], [
            'id' => Str::uuid(),
            'created_by_id' => $admin->id,
            'description' => '30-second conversion video',
            'file_path' => 'creative-assets/facebook-lead-form.mp4',
            'file_url' => 'https://example.com/facebook-lead-form.mp4',
            'preview_url' => 'https://placehold.co/1280x720/png?text=Video+Preview',
            'type' => 'video',
            'mime_type' => 'video/mp4',
            'file_size_kb' => 5120,
            'tags' => ['facebook', 'video', 'conversion'],
            'status' => 'active',
            'dimensions' => null,
            'download_count' => 8,
        ]);

        // Seed real activities for the admin user
        Activity::create([
            'user_id' => $admin->id,
            'type' => 'offer',
            'title' => 'Meta Ads Offer Performance Update',
            'value' => '$1,250.00',
            'icon' => 'trending-up',
            'link' => '/offers/1',
            'event_type' => 'offer_performance',
            'metadata' => ['offer_id' => 1, 'earnings' => 1250],
        ]);

        Activity::create([
            'user_id' => $admin->id,
            'type' => 'conversion',
            'title' => 'New conversion earned',
            'value' => '+$25.00',
            'icon' => 'check-circle',
            'link' => '/dashboard',
            'event_type' => 'conversion_earned',
            'metadata' => ['conversion_id' => 1, 'amount' => 25],
        ]);

        Activity::create([
            'user_id' => $admin->id,
            'type' => 'asset',
            'title' => 'Creative asset uploaded',
            'value' => 'Meta Ad Thumbnail v1',
            'icon' => 'image',
            'link' => '/creative-library',
            'event_type' => 'asset_uploaded',
            'metadata' => ['asset_id' => 1, 'filename' => 'meta-thumbnail-v1.jpg'],
        ]);

        Activity::create([
            'user_id' => $admin->id,
            'type' => 'enrollment',
            'title' => 'Course enrollment',
            'value' => 'Meta Ads — Full Setup',
            'icon' => 'book',
            'link' => '/university',
            'event_type' => 'enrollment_created',
            'metadata' => ['course_id' => 1, 'course_title' => 'Meta Ads — Full Setup'],
        ]);

        Activity::create([
            'user_id' => $admin->id,
            'type' => 'system',
            'title' => 'Platform update',
            'value' => 'Live activity feed is now enabled',
            'icon' => 'bell',
            'link' => null,
            'event_type' => 'system_update',
            'metadata' => ['feature' => 'live_activity_feed'],
        ]);
    }
}
