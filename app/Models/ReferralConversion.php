<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralConversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_id',
        'referred_user_id',
        'commission',
        'status'
    ];

    public function referral()
    {
        return $this->belongsTo(Referral::class);
    }

    public function referredUser()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}
