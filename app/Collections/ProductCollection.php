<?php

namespace App\Collections;

use Illuminate\Database\Eloquent\Collection;

class ProductCollection extends Collection
{
    public function inStock(): self
    {
        return $this->filter(fn($product) => $product->quantity > 0);
    }

    public function byPriceRange(float $min, float $max): self
    {
        return $this->filter(fn($product) => $product->price >= $min && $product->price <= $max);
    }

    public function featured(): self
    {
        return $this->filter(function ($product) {
            $tags = is_array($product->tags) ? $product->tags : [];
            return in_array('featured', $tags);
        });
    }

    public function onSale(): self
    {
        return $this->filter(fn($product) => $product->discount_price !== null && $product->discount_price < $product->price);
    }

    public function totalValue(): float
    {
        return (float) $this->sum(fn($product) => $product->quantity * $product->price);
    }
}
