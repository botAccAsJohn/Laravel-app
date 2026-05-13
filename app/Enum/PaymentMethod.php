<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CARD = 'card';
    case UPI = 'upi';
    case WALLET = 'wallet';
    case COD = 'cod';
    case EMI = 'emi';
    case NETBANKING = 'netbanking';

    /**
     * Optional: Get all enum values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
