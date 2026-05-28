@auth('admin')
    Hello, {{ Auth::guard('admin')->user()->name }}
@endauth