<!DOCTYPE html>
<html>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js']);

<head>
    <title>My App</title>
</head>

<body>
    @include('layouts.navbar')

    <header>
        <h1>My Laravel App</h1>
    </header>
    <main>
        @yield('content')
    </main>

    <footer>
        <p>Copyright 2026</p>
    </footer>

</body>

</html>