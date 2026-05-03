<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferApproval extends Model
{
    use HasFactory;

    protected $fillable = ['offer_id', 'requested_by', 'requested_at', 'status', 'reviewed_by', 'reviewed_at', 'notes'];

    protected $casts = ['requested_at' => 'datetime', 'reviewed_at' => 'datetime'];
}
