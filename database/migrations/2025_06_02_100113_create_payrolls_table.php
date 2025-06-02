<?php

use App\Models\User;
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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->references('id')->on('users');
            $table->foreignIdFor(User::class, 'pic_id')->references('id')->on('users');
            $table->integer('total_attendance')->default(0);
            $table->integer('total_daily_salary')->default(0);
            $table->integer('total_overtime')->default(0);
            $table->integer('total_late_cut')->default(0);
            $table->integer('total_loan')->default(0);
            $table->string('datetime')->default('-');
            $table->text('notes');
            $table->enum('status', ['waiting', 'approved', 'rejected', 'cancelled'])->default('waiting');
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
