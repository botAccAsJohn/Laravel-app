<?php

namespace App\Services;

class ProductService
{
    /**
     * Create a new class instance.
     */
    public function getAll()
    {
        return ["Laptop", "Phone", "Tablet"];
    }

    public function store($data)
    {
        return "Product saved!";
    }
}
