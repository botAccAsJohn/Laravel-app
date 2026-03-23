<?php

namespace App\Services;

class ProductService
{
    /**
     * Create a new class instance.
     */
    public function getAll()
    {
        return $products = [
            [
                'name' => 'Laptop',
                'price' => 50000
            ],
            [
                'name' => 'Phone',
                'price' => 20000
            ],
            [
                'name' => 'Headphones',
                'price' => 3000
            ],
        ];
    }

    public function store($data)
    {
        return "Product saved!";
    }
}
