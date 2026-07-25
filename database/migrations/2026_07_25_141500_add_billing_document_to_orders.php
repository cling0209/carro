<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('document_type', 20)->default('boleta')->after('customer_name');
            $table->string('billing_rut', 20)->nullable()->after('document_type');
            $table->string('billing_business_name', 180)->nullable()->after('billing_rut');
            $table->string('billing_activity', 180)->nullable()->after('billing_business_name');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'document_type',
                'billing_rut',
                'billing_business_name',
                'billing_activity',
            ]);
        });
    }
};
