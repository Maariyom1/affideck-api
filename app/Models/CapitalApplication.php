<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapitalApplication extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'amount', 'status', 'notes', 'metadata'];

    protected $casts = ['metadata' => 'array'];
}
