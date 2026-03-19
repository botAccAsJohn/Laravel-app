<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    // HasFactory trait enables Product::factory() for seeding/testing

    // fillable tells Laravel which columns are safe to mass-assign
    // Mass assignment = passing an array to Product::create([...])
    // Any column NOT in this list will be ignored for security
    protected $fillable = [
        'name',
        'description',
        'price',
        'slug',
        'is_active',
    ];

    // casts tells Laravel how to convert database values
    // The database stores everything as strings — casts fix the types
    protected $casts = [
        'price'     => 'decimal:2', // always return price with 2 decimal places
        'is_active' => 'boolean',   // return true/false instead of 1/0
    ];

    // ─────────────────────────────────────────────────
    // CUSTOM ROUTE MODEL BINDING KEY
    // ─────────────────────────────────────────────────
    // By default, Laravel binds by 'id' — so /products/{product}
    // runs: SELECT * FROM products WHERE id = {product}
    //
    // Override this to bind by 'slug' instead:
    // /products/ergonomic-rubber-chair-1234 → finds by slug
    //
    // Comment this method out to use default id binding
    // ─────────────────────────────────────────────────
    public function getRouteKeyName(): string
    {
        return 'slug'; // ← bind by slug instead of id
    }
}
