<body>
    <h1>Products</h1>
    <ul>
        @foreach ($products as $product)
        <li>{{ $product }}</li>
        @endforeach
    </ul>
    @if(session('success'))
    <div style="color: green; padding: 10px; border: 1px solid green;">
        {{ session('success') }}
    </div>
    @endif
    <a href="{{ route('download') }}">Download File</a>
</body>