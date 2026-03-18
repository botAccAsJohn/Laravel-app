<?php

namespace App\Services;

use Illuminate\View\View;

class GreetingService
{
    public function __construct(private string $language = 'en') {}
    public function greet(string $name): View
    {
        $greetingMessage = match ($this->language) {
            'en' => "Hello, $name!",
            'es' => "¡Hola, $name!",
            'fr' => "Bonjour, $name!",
            default => "Hello, $name!"
        };

        return view('header', [
            'appName' => config('greeting.app_name', 'My App'),
            'greeting' => $greetingMessage,
        ]);
    }
}
