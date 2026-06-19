<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
      /**
       * Run the migrations.
       */
      protected $connection = 'analytics';



      public function up(): void
      {
            Schema::dropIfExists('order_analytics');
            Schema::create('order_analytics', function (Blueprint $table) {
                  $table->id();

                  // ── Time dimension ──────────────────────────────────────
                  $table->date('report_date')->comment('The calendar date this snapshot covers');
                  $table->enum('period_type', [
                        'daily',
                        'weekly',
                        'monthly',
                  ])->default('daily')->index();

                  // ── Order volume metrics ────────────────────────────────
                  $table->unsignedInteger('total_orders')->default(0);
                  $table->unsignedInteger('pending_orders')->default(0);
                  $table->unsignedInteger('confirmed_orders')->default(0);
                  $table->unsignedInteger('processing_orders')->default(0);
                  $table->unsignedInteger('shipped_orders')->default(0);
                  $table->unsignedInteger('delivered_orders')->default(0);
                  $table->unsignedInteger('cancelled_orders')->default(0);
                  $table->unsignedInteger('refunded_orders')->default(0);

                  // ── Revenue metrics (mirrors orders decimal precision) ──
                  $table->decimal('gross_revenue', 12, 2)->default(0)
                        ->comment('Sum of total_amount across orders');
                  $table->decimal('total_discount', 12, 2)->default(0)
                        ->comment('Sum of discount_amount across orders');
                  $table->decimal('net_revenue', 12, 2)->default(0)
                        ->comment('Sum of final_amount across orders');
                  $table->decimal('average_order_value', 10, 2)->default(0)
                        ->comment('net_revenue / total_orders');

                  // ── Item-level metrics ──────────────────────────────────
                  $table->unsignedInteger('total_items_sold')->default(0)
                        ->comment('Sum of quantity from order_items');
                  $table->unsignedInteger('unique_products_sold')->default(0)
                        ->comment('Distinct product_id count from order_items');

                  // ── Payment method breakdown ────────────────────────────
                  $table->unsignedInteger('card_payments')->default(0);
                  $table->unsignedInteger('upi_payments')->default(0);
                  $table->unsignedInteger('wallet_payments')->default(0);
                  $table->unsignedInteger('cod_payments')->default(0);
                  $table->unsignedInteger('emi_payments')->default(0);
                  $table->unsignedInteger('netbanking_payments')->default(0);

                  // ── Customer metrics ────────────────────────────────────
                  $table->unsignedInteger('registered_customer_orders')->default(0)
                        ->comment('Orders where user_id IS NOT NULL');
                  $table->unsignedInteger('guest_orders')->default(0)
                        ->comment('Orders where user_id IS NULL (guest_email used)');
                  $table->unsignedInteger('new_customers')->default(0)
                        ->comment('Customers placing their first-ever order on this date');
                  $table->unsignedInteger('returning_customers')->default(0)
                        ->comment('Customers who have ordered before');

                  // ── Coupon usage metrics ────────────────────────────────
                  $table->unsignedInteger('orders_with_coupon')->default(0)
                        ->comment('Orders where coupon_code IS NOT NULL');
                  $table->decimal('coupon_discount_total', 10, 2)->default(0)
                        ->comment('Sum of discount_amount for coupon orders');

                  // ── Top performers (JSON snapshots) ─────────────────────
                  $table->json('top_products')->nullable()
                        ->comment('JSON array of {product_id, name, qty, revenue}');
                  $table->json('top_categories')->nullable()
                        ->comment('JSON array of {category_id, name, qty, revenue}');

                  $table->timestamps();

                  // ── Indexes for fast dashboard queries ──────────────────
                  $table->unique(['report_date', 'period_type']);
                  $table->index('report_date');
            });
      }

      /**
       * Reverse the migrations.
       */
      public function down(): void
      {
            Schema::dropIfExists('order_analytics');
      }
};
