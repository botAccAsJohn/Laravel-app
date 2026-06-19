<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LoggerService
{
    public string $id;

    public function __construct()
    {
        $this->id = spl_object_id($this);
        Log::info("LoggerService CREATED — instance ID: {$this->id}");
    }
    public function log(string $message): void
    {
        Log::info("[Instance {$this->id}] $message");
    }
}
