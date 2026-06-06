<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('shipping_comuna_settings');
    }

    public function down(): void
    {
        // Replaced by global shipping_settings key fallback_additional_clp
    }
};
