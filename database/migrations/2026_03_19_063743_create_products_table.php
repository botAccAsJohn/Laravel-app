<?php
// database/migrations/xxxx_xx_xx_create_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {

            $table->id();
            // id() creates an auto-incrementing BIGINT primary key column
            // This is the column Laravel uses for model binding by default

            $table->string('name');
            // string() = VARCHAR(255) — stores the product name

            $table->text('description')->nullable();
            // text() = TEXT column — longer content
            // nullable() means this column can be empty (NULL)

            $table->integer('quantity')->default(0);

            $table->decimal('price', 8, 2);
            // decimal(total_digits, decimal_places)
            // 8, 2 means up to 999999.99 — perfect for prices

            $table->string('slug')->unique();
            // slug = URL-friendly version of the name
            // e.g. "Running Shoes" → "running-shoes"
            // unique() means no two products can have the same slug

            $table->boolean('is_active')->default(true);
            // boolean = TINYINT(1) — true/false flag
            // default(true) means new products are active by default

            $table->timestamps();
            // timestamps() adds created_at and updated_at columns
            // Laravel updates these automatically
        });
    }

    public function down(): void
    {
        // down() is called when you rollback the migration
        Schema::dropIfExists('products');
    }
};
