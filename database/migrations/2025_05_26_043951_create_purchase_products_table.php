<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_products_companies', function (Blueprint $table) {
            $table->id();

            $table->string('doc_no')->nullable(); // Relasi ke purchases
            $table->foreign('doc_no')
                  ->references('doc_no')->on('purchases')
                  ->nullOnDelete();

            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();

            $table->string('product_name');
            $table->string('harga')->nullable();
            $table->string('stok')->nullable();
            $table->string('subtotal_harga_product');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_products');
    }
};
