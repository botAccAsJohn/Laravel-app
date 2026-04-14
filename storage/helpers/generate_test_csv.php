<?php

$filePath = __DIR__ . '/large_products.csv';
$rowCount = 1200;

$categories = [1, 2, 3, 4, 5]; // Based on previous check
$headers = ['name', 'description', 'price', 'discount_price', 'tags', 'category_id', 'slug', 'quantity'];

$file = fopen($filePath, 'w');
fputcsv($file, $headers);

for ($i = 1; $i <= $rowCount; $i++) {
    $price = rand(100, 10000) / 100;
    $row = [
        "Product $i",
        "Description for product $i. This is a large import test.",
        $price,
        $price * 0.9,
        json_encode(['test', 'bulk', 'import']),
        $categories[array_rand($categories)],
        "product-$i-" . time(),
        rand(1, 100)
    ];
    fputcsv($file, $row);
}

fclose($file);

echo "Created $filePath with $rowCount rows.\n";
