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
        Schema::table('attendances', function (Blueprint $table) {
            $table->integer('daily_salary')->default(0);
            $table->integer('hourly_salary')->default(0);
            $table->integer('hourly_overtime_salary')->default(0);
            $table->integer('makan')->default(0);
            $table->integer('transport')->default(0);
            $table->integer('bonus_ontime')->default(0);
            $table->integer('late_cut')->default(0);
            $table->integer('late_minutes')->default(0);
            $table->boolean('is_settled')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'daily_salary',
                'hourly_salary',
                'hourly_overtime_salary',
                'makan',
                'transport',
                'bonus_ontime',
                'late_cut',
                'late_minutes',
                'is_settled',
            ]);
        });
    }
};
