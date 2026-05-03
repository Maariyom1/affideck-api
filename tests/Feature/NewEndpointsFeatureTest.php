<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Community;
use App\Models\Offer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewEndpointsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_offer_request_approval_creates_record(): void
    {
        $user = User::factory()->create();
        $tokens = ApiToken::issuePair($user);
        $offer = Offer::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

        $response = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->postJson('/api/offers/'.$offer->id.'/request-approval');

        $response->assertCreated()->assertJsonPath('data.status', 'pending');
        $this->assertDatabaseHas('offer_approvals', ['offer_id' => $offer->id, 'requested_by' => $user->id]);
    }

    public function test_offer_favorite_persists(): void
    {
        $user = User::factory()->create();
        $tokens = ApiToken::issuePair($user);
        $offer = Offer::factory()->create(['status' => 'published']);

        $response = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->postJson('/api/offers/'.$offer->id.'/favorite');

        $response->assertCreated();
        $this->assertDatabaseHas('offer_favorites', ['offer_id' => $offer->id, 'user_id' => $user->id]);
    }

    public function test_verify_identity_updates_setting(): void
    {
        $user = User::factory()->create();
        $tokens = ApiToken::issuePair($user);
        config(['services.kyc.provider' => 'mock']);

        $response = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->postJson('/api/settings/verify-identity', ['first_name' => 'John']);

        $response->assertStatus(202)->assertJsonStructure(['data' => ['status', 'id']]);
        $this->assertDatabaseHas('settings', ['user_id' => $user->id]);
        $s = Setting::where('user_id', $user->id)->first();
        $this->assertNotNull($s->preferences['kyc']);
    }

    public function test_upload_sign_returns_url(): void
    {
        $user = User::factory()->create();
        $tokens = ApiToken::issuePair($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->postJson('/api/uploads/sign', ['filename' => 'test.png', 'contentType' => 'image/png']);

        $response->assertOk()->assertJsonStructure(['data' => ['signed_url']]);
    }

    public function test_referral_share_link_and_community_flow_and_capital_eligibility(): void
    {
        $user = User::factory()->create();
        $tokens = ApiToken::issuePair($user);

        // Referral
        $r = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])->getJson('/api/referrals/share-link');
        $r->assertOk()->assertJsonStructure(['data' => ['code', 'link']]);

        // Communities: create and join
        $c = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])->postJson('/api/communities', ['name' => 'Test', 'slug' => 'test']);
        $c->assertCreated()->assertJsonPath('data.slug', 'test');
        $communityId = $c->json('data.id');

        $join = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])->postJson('/api/communities/'.$communityId.'/join');
        $join->assertCreated();

        // Capital eligibility (new user <7 days should be ineligible)
        $elig = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])->getJson('/api/capital/eligibility');
        $elig->assertOk()->assertJsonStructure(['data' => ['eligible']]);
    }
}
