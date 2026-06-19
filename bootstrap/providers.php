<?php

return [
    App\Providers\AdminServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\ObserverServiceProvider::class,
    App\Providers\RateLimitServiceProvider::class,
    Resend\Laravel\ResendServiceProvider::class,
    App\Providers\SecretsVaultServiceProvider::class, // Exercise 54.4
];
