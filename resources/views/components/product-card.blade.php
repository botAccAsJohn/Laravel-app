@props(['name', 'price'])

<div style="border:1px solid #ccc; padding:10px; margin:10px;">
    <h3>{{ $name }}</h3>
    <p>Price: ₹{{ $price }}</p>
</div>