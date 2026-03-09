<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $payment;

    public function __construct(PaymentService $payment)
    {
        $this->payment = $payment;
    }

    public function pay()
    {
        return $this->payment->charge(500);
    }
}
