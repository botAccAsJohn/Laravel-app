<?php

namespace App\Collections;

use Illuminate\Database\Eloquent\Collection;

class ProductCollection extends Collection
{
    /**
     * Filter products with stock > 0.
     *
     * @return self
     */
    public function inStock(): self
    {
        return $this->filter(fn($product) => $product->quantity > 0);
    }

    /**
     * Filter products by price range.
     *
     * @param float $min
     * @param float $max
     * @return self
     */
    public function byPriceRange(float $min, float $max): self
    {
        return $this->filter(fn($product) => $product->price >= $min && $product->price <= $max);
    }

    /**
     * Filter featured products.
     *
     * @return self
     */
    public function featured(): self
    {
        return $this->filter(function ($product) {
            $tags = is_array($product->tags) ? $product->tags : [];
            return in_array('featured', $tags);
        });
    }

    /**
     * Filter products currently on sale (has discount price).
     *
     * @return self
     */
    public function onSale(): self
    {
        return $this->filter(fn($product) => $product->discount_price !== null && $product->discount_price < $product->price);
    }

    /**
     * Calculate total inventory value (quantity * price).
     *
     * @return float
     */
    public function totalValue(): float
    {
        return (float) $this->sum(fn($product) => $product->quantity * $product->price);
    }
}
