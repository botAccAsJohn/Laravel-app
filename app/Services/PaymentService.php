<?php

namespace App\Services;

class PaymentService
{
    public function charge(int $amount): string
    {
        return "Charged ₹{$amount}";
    }
}
