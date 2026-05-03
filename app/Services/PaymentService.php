<?php

namespace App\Services;

class PaymentService
{
    public function createPaymentIntent(float $amount, string $currency = 'usd'): array
    {
        return ['id' => 'pi_mock_'.uniqid(), 'client_secret' => 'secret_mock'];
    }
}
