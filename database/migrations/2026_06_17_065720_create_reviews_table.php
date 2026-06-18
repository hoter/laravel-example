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
        Schema::create('reviews', function (Blueprint $table) {
            // Поля: id, product_id (внешний ключ), user_name, rating (1-5), comment, is_approved, created_at, updated_at
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->string('user_name');
            $table->integer('rating')->default(5);
            $table->text('comment');
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
