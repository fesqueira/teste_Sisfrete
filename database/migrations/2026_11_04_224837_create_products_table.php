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
            $table->string('sku')->unique();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->index();
            $table->unsignedInteger('stock')->default(0);
            $table->decimal('weight_kg', 8, 2);
            $table->decimal('height_cm', 8, 2);
            $table->decimal('width_cm', 8, 2);
            $table->decimal('length_cm', 8, 2);
            $table->boolean('active')->default(true);
            $table->timestamps();
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
