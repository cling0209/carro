<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_comuna_weight_rates', function (Blueprint $table) {
            $table->id();
            $table->string('region');
            $table->string('comuna');
            $table->string('label');
            $table->decimal('min_weight_kg', 8, 3)->default(0);
            $table->decimal('max_weight_kg', 8, 3)->nullable();
            $table->decimal('price', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['region', 'comuna']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_comuna_weight_rates');
    }
};
