<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Offer;
use App\Models\OfferApproval;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOfferApprovalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_offer_approval(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $tokens = ApiToken::issuePair($admin);

        $owner = User::factory()->create();
        $offer = Offer::factory()->create(['user_id' => $owner->id, 'status' => 'draft']);

        $approval = OfferApproval::create([
            'offer_id' => $offer->id,
            'requested_by' => $owner->id,
            'requested_at' => now(),
            'status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->patchJson('/api/admin/offer-approvals/'.$approval->id.'/approve');

        $response->assertOk();

        $this->assertDatabaseHas('offer_approvals', ['id' => $approval->id, 'status' => 'approved', 'reviewed_by' => $admin->id]);
        $this->assertDatabaseHas('offers', ['id' => $offer->id, 'status' => 'published']);
    }

    public function test_admin_can_deny_offer_approval(): void
    {
        $admin = User::factory()->create(['email' => 'admin2@example.com']);
        $tokens = ApiToken::issuePair($admin);

        $owner = User::factory()->create();
        $offer = Offer::factory()->create(['user_id' => $owner->id, 'status' => 'draft']);

        $approval = OfferApproval::create([
            'offer_id' => $offer->id,
            'requested_by' => $owner->id,
            'requested_at' => now(),
            'status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->patchJson('/api/admin/offer-approvals/'.$approval->id.'/deny');

        $response->assertOk();

        $this->assertDatabaseHas('offer_approvals', ['id' => $approval->id, 'status' => 'denied', 'reviewed_by' => $admin->id]);
        $this->assertDatabaseHas('offers', ['id' => $offer->id, 'status' => 'archived']);
    }

    public function test_non_admin_cannot_approve(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $tokens = ApiToken::issuePair($user);

        $owner = User::factory()->create();
        $offer = Offer::factory()->create(['user_id' => $owner->id, 'status' => 'draft']);

        $approval = OfferApproval::create([
            'offer_id' => $offer->id,
            'requested_by' => $owner->id,
            'requested_at' => now(),
            'status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->patchJson('/api/admin/offer-approvals/'.$approval->id.'/approve');

        $response->assertStatus(403);
    }
}
