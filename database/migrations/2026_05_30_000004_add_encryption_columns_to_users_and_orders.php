<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exercise 54.2: Add columns for encrypted casting and blind indexes.
     */
    public function up(): void
    {
        // 1. Add columns to users table
        Schema::table('users', function (Blueprint $table) {
            $table->text('address')->nullable();
            $table->string('tax_id')->nullable();
            $table->text('extra_attributes')->nullable(); // For demonstrating encrypted:array

            // Blind indexes for lookups
            $table->string('phone_bindex', 64)->nullable()->index();
            $table->string('tax_id_bindex', 64)->nullable()->index();
        });

        // 2. Add columns to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'address',
                'tax_id',
                'extra_attributes',
                'phone_bindex',
                'tax_id_bindex'
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'billing_address',
                'shipping_address'
            ]);
        });
    }
};
