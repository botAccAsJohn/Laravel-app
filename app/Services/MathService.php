<?php

namespace App\Services;

class MathService
{
    public function add(float $a, float $b): float
    {
        return $a + $b;
    }

    public function subtract(float $a, float $b): float
    {
        return $a - $b;
    }

    public function multiply(float $a, float $b): float
    {
        return $a * $b;
    }

    public function divide(float $a, float $b): float|string
    {
        if ($b === 0.0) {
            return 'Error: division by zero';
        }
        return $a / $b;
    }
    public function percentage(float $total, float $percent): float
    {
        return ($total * $percent) / 100;
    }
}
