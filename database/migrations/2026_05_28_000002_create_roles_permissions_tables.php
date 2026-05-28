<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Exercise 50.4 — Role/Permission tables.
//
// Schema design:
//   roles            — named roles (admin, manager, support, customer)
//   permissions      — named capabilities (manage_products, view_reports …)
//   role_user        — pivot: which roles a user holds (many-to-many)
//   permission_role  — pivot: which permissions a role grants (many-to-many)
//
// This is a standard RBAC (Role-Based Access Control) schema.
// We also keep the legacy `users.role` column for backward compatibility.

return new class extends Migration
{
    public function up(): void
    {
        // ── roles ─────────────────────────────────────────────────────────────
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();          // e.g. 'manager'
            $table->string('display_name');            // e.g. 'Store Manager'
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // ── permissions ───────────────────────────────────────────────────────
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();          // e.g. 'manage_products'
            $table->string('display_name');            // e.g. 'Manage Products'
            $table->string('group')->default('general'); // for UI grouping
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // ── role_user (user ↔ role pivot) ─────────────────────────────────────
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()
                  ->constrained('admins')->nullOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->primary(['user_id', 'role_id']);
        });

        // ── permission_role (permission ↔ role pivot) ─────────────────────────
        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
