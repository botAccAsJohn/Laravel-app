<!DOCTYPE html>
<html>

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