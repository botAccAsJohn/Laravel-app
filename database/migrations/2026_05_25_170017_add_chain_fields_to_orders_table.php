<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Stores the payment gateway reference (e.g. Stripe charge ID)
            $table->string('payment_ref')->nullable()->after('payment_method');

            // True for digital/downloadable orders — skips ReserveStock chain step
            $table->boolean('is_digital')->default(false)->after('payment_ref');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_ref', 'is_digital']);
        });
    }
};
