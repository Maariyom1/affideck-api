<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'payout' => $this->payout,
            'owner_id' => $this->user_id,
            'status' => $this->status,
            'tags' => $this->tags ?? [],
            'categories' => $this->categories ?? [],
            'geo' => $this->geo ?? [],
            'clicks' => $this->clicks,
            'conversions' => $this->conversions,
            'earnings' => $this->earnings,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
