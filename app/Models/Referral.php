<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'code', 'source'];

    public function conversions()
    {
        return $this->hasMany(ReferralConversion::class);
    }
}
