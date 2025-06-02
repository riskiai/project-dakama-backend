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
        Schema::table('log_purchases', function (Blueprint $table) {
            $table->foreignId('purchase_status_id')
                  ->nullable()
                  ->after('tab') // letakkan setelah kolom 'tab'
                  ->constrained('purchase_status')
                  ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('log_purchases', function (Blueprint $table) {
                 $table->dropForeign(['purchase_status_id']);
                 $table->dropColumn('purchase_status_id');
        });
    }
};
