<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserProfileResource;
use Illuminate\Http\Resources\Json\JsonResource;

class MeController extends Controller
{
    public function __invoke(): JsonResource
    {
        return new UserProfileResource(request()->user());
    }
}