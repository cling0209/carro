<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->softDeletes();
            $table->dropUnique(['sku']);
            $table->dropUnique(['slug']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->softDeletes();
            $table->dropUnique(['slug']);
        });

        DB::statement('CREATE UNIQUE INDEX products_sku_unique ON products (sku) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX products_slug_unique ON products (slug) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX categories_slug_unique ON categories (slug) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS products_sku_unique');
        DB::statement('DROP INDEX IF EXISTS products_slug_unique');
        DB::statement('DROP INDEX IF EXISTS categories_slug_unique');

        Schema::table('products', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->unique('sku');
            $table->unique('slug');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->unique('slug');
        });
    }
};
