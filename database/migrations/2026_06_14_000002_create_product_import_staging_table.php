<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_import_staging', function (Blueprint $table) {
            $table->uuid('upload_id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->string('username', 50);
            $table->string('original_name', 255);
            $table->string('source_path', 500)->nullable();
            $table->json('columns');
            $table->unsignedInteger('total_rows');
            $table->text('csv_content')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_import_staging');
    }
};
