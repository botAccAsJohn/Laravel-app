@extends('layouts.app')

@section('content')
<h1>Products</h1>
<ul>
    @foreach ($products as $product)
    <li>{{ $product['name'] }}</li>
    @endforeach
</ul>
@if(session('success'))
<div style="color: green; padding: 10px; border: 1px solid green;">
    {{ session('success') }}
</div>
@endif
@foreach($products as $product)
<x-product-card :name="$product['name']" :price="$product['price']" />
@endforeach

<a href="{{ route('download') }}">Download File</a>
@endsection