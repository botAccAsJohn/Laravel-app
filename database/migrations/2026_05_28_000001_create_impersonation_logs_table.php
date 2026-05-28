<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Stores one row every time an admin impersonates a customer.
     * The `stopped_at` column is NULL while the session is active.
     */
    public function up(): void
    {
        Schema::create('impersonation_logs', function (Blueprint $table) {
            $table->id();

            // The admin who initiated the impersonation
            $table->unsignedBigInteger('admin_id');
            $table->string('admin_email');       // snapshot — survives admin deletion

            // The customer being impersonated
            $table->unsignedBigInteger('target_user_id');
            $table->string('target_email');      // snapshot

            // Context
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('stopped_at')->nullable(); // set when impersonation ends

            $table->timestamps();

            $table->index('admin_id');
            $table->index('target_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('impersonation_logs');
    }
};
