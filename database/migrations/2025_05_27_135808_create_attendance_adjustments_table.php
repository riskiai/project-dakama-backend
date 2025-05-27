<?php

use App\Models\Attendance;
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
        Schema::create('attendance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->references('id')->on('users')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'pic_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignIdFor(Attendance::class)->references('id')->on('attendances')->cascadeOnDelete();
            $table->dateTime('old_start_time');
            $table->dateTime('old_end_time');
            $table->dateTime('new_start_time');
            $table->dateTime('new_end_time');
            $table->string('reason')->default('-');
            $table->enum('status', ['waiting', 'approved', 'rejected', 'cancelled'])->default('waiting');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_adjustments');
    }
};
