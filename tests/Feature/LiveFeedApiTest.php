<?php

namespace Tests\Feature;

use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LiveFeedApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_feed_respects_pagination_and_rotates_over_time(): void
    {
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-05-12 10:30:00', 'UTC'));

        // Create a test user for activities
        $user = \App\Models\User::factory()->create();

        // Create 16 sample activities to test pagination
        for ($i = 1; $i <= 16; $i++) {
            Activity::create([
                'user_id' => $user->id,
                'type' => 'offer',
                'title' => "Activity $i",
                'value' => "$i.00",
                'icon' => 'activity',
                'link' => null,
                'event_type' => 'test_activity',
                'metadata' => ['index' => $i],
                'created_at' => Carbon::parse('2026-05-12 10:30:00', 'UTC')->subSeconds(16 - $i),
            ]);
        }

        $firstResponse = $this->getJson('/api/live-feed?page=1&per_page=8');

        $firstResponse
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 8)
            ->assertJsonPath('meta.total', 16)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.has_new_data', true)
            ->assertJsonCount(8, 'data');

        $firstTexts = array_column($firstResponse->json('data'), 'text');

        Carbon::setTestNow(Carbon::parse('2026-05-12 10:30:05', 'UTC'));

        $sameWindowResponse = $this->getJson('/api/live-feed?page=1&per_page=8');

        $sameWindowResponse
            ->assertOk()
            ->assertJsonPath('meta.has_new_data', false);

        Carbon::setTestNow(Carbon::parse('2026-05-12 10:30:10', 'UTC'));

        $secondResponse = $this->getJson('/api/live-feed?page=1&per_page=8');
        $secondResponse->assertJsonPath('meta.has_new_data', true);
        $secondTexts = array_column($secondResponse->json('data'), 'text');

        $this->assertNotSame($firstTexts, $secondTexts);

        $pageTwoResponse = $this->getJson('/api/live-feed?page=2&per_page=8');

        $pageTwoResponse
            ->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonCount(8, 'data');

        Carbon::setTestNow();
    }
}
