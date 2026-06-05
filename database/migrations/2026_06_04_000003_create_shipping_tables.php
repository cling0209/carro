<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('weight_kg', 8, 3)->nullable()->after('stock');
        });

        Schema::create('shipping_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value');
            $table->timestamps();
        });

        Schema::create('shipping_weight_rates', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->decimal('min_weight_kg', 8, 3)->default(0);
            $table->decimal('max_weight_kg', 8, 3)->nullable();
            $table->decimal('price', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_zone', 20)->nullable()->after('shipping_amount');
            $table->decimal('shipping_total_weight_kg', 8, 3)->nullable()->after('shipping_zone');
            $table->string('shipping_rate_type', 20)->nullable()->after('shipping_total_weight_kg');
            $table->string('shipping_rate_label')->nullable()->after('shipping_rate_type');
            $table->foreignId('shipping_weight_rate_id')->nullable()->after('shipping_rate_label')
                ->constrained('shipping_weight_rates')->nullOnDelete();
            $table->jsonb('shipping_metadata')->nullable()->after('shipping_weight_rate_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_weight_rate_id');
            $table->dropColumn([
                'shipping_zone',
                'shipping_total_weight_kg',
                'shipping_rate_type',
                'shipping_rate_label',
                'shipping_metadata',
            ]);
        });

        Schema::dropIfExists('shipping_weight_rates');
        Schema::dropIfExists('shipping_settings');
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('weight_kg');
        });
    }
};
