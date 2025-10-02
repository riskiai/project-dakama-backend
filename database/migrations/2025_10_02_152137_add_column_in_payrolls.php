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
        Schema::table('payrolls', function (Blueprint $table) {
            $table->double('total_hour_attend')->default(0);
            $table->double('total_hour_overtime')->default(0);
            $table->double('total_makan_attend')->default(0);
            $table->double('total_makan_overtime')->default(0);
            $table->double('bonus')->default(0);
            $table->double('transport')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn('total_hour_attend');
            $table->dropColumn('total_hour_overtime');
            $table->dropColumn('total_makan_attend');
            $table->dropColumn('total_makan_overtime');
            $table->dropColumn('bonus');
            $table->dropColumn('transport');
        });
    }
};
