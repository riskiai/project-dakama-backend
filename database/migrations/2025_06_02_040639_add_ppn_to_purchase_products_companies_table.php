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
        Schema::table('purchase_products_companies', function (Blueprint $table) {
              $table->string('ppn', 50)->nullable()->after('subtotal_harga_product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_products_companies', function (Blueprint $table) {
             $table->dropColumn('ppn');
        });
    }
};
