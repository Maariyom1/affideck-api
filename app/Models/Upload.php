<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'filename', 'path', 'mime', 'size', 'meta'];

    protected $casts = ['meta' => 'array'];
}
