<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('guest_email')->nullable()->index();
            $table->enum('status', [
                'pending',
                'confirmed',
                'processing',
                'shipped',
                'delivered',
                'cancelled',
                'refunded',
            ])->default('pending')->index();
            $table->enum('payment_method', [
                'card',
                'upi',
                'wallet',
                'cod',
                'emi',
                'netbanking',
            ]);
            $table->string('address');
            $table->string('phone')->nullable();
            $table->string('coupon_code')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('final_amount', 10, 2);
            $table->timestamp('placed_at')->useCurrent()->index();
            $table->string('invoice_path')->nullable();
            $table->timestamp('updated_at')->useCurrent();
            $table->index(['user_id', 'status', 'placed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
