<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->decimal('price', 10, 2)->index();
            $table->decimal('discount_price', 10, 2)->nullable()->index();
            $table->json('tags')->nullable();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('slug', 255)->unique();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->nullableMorphs('created_by');
            $table->nullableMorphs('updated_by');   
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->fullText(['name', 'description']);
            }
            $table->softDeletes();
            $table->index(['category_id', 'is_active', 'deleted_at', 'created_at']);
            $table->index(['category_id', 'is_active', 'deleted_at', 'price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
