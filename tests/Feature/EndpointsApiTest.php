<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\BlogPost;
use App\Models\CmsPage;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndpointsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_offers_endpoint(): void
    {
        $user = User::factory()->create();
        
        Offer::factory()->create([
            'user_id' => $user->id,
            'status' => 'published',
            'name' => 'Test Offer',
        ]);
        
        $response = $this->getJson('/api/offers');
        
        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'type', 'payout', 'status'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    public function test_authenticated_user_can_create_offer(): void
    {
        $user = User::factory()->create();
        $tokens = ApiToken::issuePair($user);
        
        $response = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->postJson('/api/offers', [
                'name' => 'New Offer',
                'description' => 'Test description',
                'type' => 'cpa',
                'payout' => 25.50,
                'tags' => ['finance', 'crypto'],
                'categories' => ['Lending'],
                'geo' => ['US', 'CA'],
            ]);
        
        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'New Offer')
            ->assertJsonPath('data.type', 'cpa');
    }

    public function test_public_marketplace_items_endpoint(): void
    {
        $user = User::factory()->create();
        
        $user->marketplaceItems()->create([
            'title' => 'Test Item',
            'description' => 'Test description',
            'price' => 99.99,
            'status' => 'published',
        ]);
        
        $response = $this->getJson('/api/marketplace/items');
        
        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta',
            ]);
    }

    public function test_public_blog_endpoint(): void
    {
        $user = User::factory()->create();
        
        BlogPost::create([
            'author_id' => $user->id,
            'title' => 'Test Post',
            'slug' => 'test-post',
            'content' => 'Test content',
            'published' => true,
            'published_at' => now(),
        ]);
        
        $response = $this->getJson('/api/blog');
        
        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta',
            ]);
    }

    public function test_dashboard_summary_endpoint(): void
    {
        $user = User::factory()->create();
        $tokens = ApiToken::issuePair($user);
        
        $user->offers()->create([
            'name' => 'Test Offer',
            'type' => 'cpa',
            'payout' => 50,
            'clicks' => 100,
            'conversions' => 10,
            'earnings' => 500,
        ]);
        
        $response = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->getJson('/api/dashboard/summary');
        
        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['earnings', 'clicks', 'conversions', 'epc', 'delta24h', 'balance'],
            ]);
    }

    public function test_contact_form_endpoint(): void
    {
        $response = $this->postJson('/api/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a test message for contact form.',
        ]);
        
        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Your message has been received. We will get back to you soon.');
    }

    public function test_search_endpoint(): void
    {
        $response = $this->getJson('/api/search?q=test');
        
        $response
            ->assertOk()
            ->assertJsonStructure(['data']);
    }
}
