<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>External API Error</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; background-color: #f3f4f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); max-width: 500px; text-align: center; }
        h1 { color: #ef4444; font-size: 1.5rem; margin-bottom: 1rem; }
        p { color: #374151; margin-bottom: 1.5rem; }
        a { display: inline-block; background-color: #3b82f6; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; transition: background-color 0.2s; }
        a:hover { background-color: #2563eb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Service Temporarily Unavailable</h1>
        <p>{{ $message ?? 'We are experiencing issues communicating with an external service. Please try again later.' }}</p>
        <a href="{{ url()->previous() }}">Go Back</a>
    </div>
</body>
</html>
