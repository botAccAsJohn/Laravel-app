<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Math extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'math.service';
    }
}
