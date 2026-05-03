<?php

namespace App\Services;

class KycService
{
    public function startVerification(array $data): array
    {
        if (config('services.kyc.provider') === 'mock' || env('KYC_PROVIDER') === 'mock') {
            return ['status' => 'started', 'id' => 'mock_'.uniqid()];
        }

        return ['status' => 'not_configured'];
    }
}
