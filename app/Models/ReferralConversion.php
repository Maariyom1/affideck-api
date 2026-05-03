<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralConversion extends Model
{
    use HasFactory;

    protected $fillable = ['referral_id', 'referred_user_id', 'commission'];
}
