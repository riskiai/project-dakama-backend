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
        Schema::table('mutation_loans', function (Blueprint $table) {
            $table->string('payment_method', 100)->nullable();
            $table->date('payment_at')->nullable();
            $table->tinyInteger('type')->default(1)->comment("1 = approval, 2 = payment");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mutation_loans', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_at', 'type']);
        });
    }
};
