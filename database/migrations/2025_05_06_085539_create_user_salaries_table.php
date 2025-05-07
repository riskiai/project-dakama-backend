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
        Schema::create('user_salaries', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedInteger('daily_salary')->default(0);
            $table->unsignedInteger('hourly_salary')->default(0);
            $table->unsignedInteger('hourly_overtime_salary')->default(0);
            $table->unsignedInteger('makan')->default(0);
            $table->unsignedInteger('transport')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_salaries');
    }
};
