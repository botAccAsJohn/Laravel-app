<?php
define('LARAVEL_START', microtime(true));
// [STEP 1] Log that we have entered the entry point
file_put_contents(
    __DIR__ . '/../storage/logs/laravel.log',
    '[' . date("Y-m-d H:i:s") . '] [STEP 1] Entry point public/index.php reached' . PHP_EOL,
    FILE_APPEND
);
// Autoloader

require __DIR__ . '/../vendor/autoload.php';

// Bootstrap application
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Run through HTTP Kernel
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());
$response->send();
$kernel->terminate($request, $response);



use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MyEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return ['my-channel'];
    }

    public function broadcastAs()
    {
        return 'my-event';
    }
}
