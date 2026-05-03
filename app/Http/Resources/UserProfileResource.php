<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $roles = str_contains(strtolower((string) $this->email), 'admin') ? ['admin'] : ['member'];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $roles,
            'plan' => 'free',
            'balance' => 0,
            'level' => 1,
            'streak' => 0,
            'unreadCounts' => [
                'notifications' => $this->unreadNotifications()->count(),
            ],
            'permissions' => $roles === ['admin'] ? ['manageUsers', 'manageOffers', 'viewAnalytics'] : ['viewDashboard'],
            'featureFlags' => [
                'canCreateOffers' => in_array('admin', $roles, true),
            ],
        ];
    }
}