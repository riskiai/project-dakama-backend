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
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->references('id')->on('users');
            $table->foreignIdFor(User::class, 'pic_id')->nullable()->references('id')->on('users');
            $table->date('request_date')->nullable();
            $table->integer('nominal')->default(0);
            $table->integer('latest')->default(0);
            $table->string('reason')->default("-");
            $table->enum('status', ['waiting', 'approved', 'rejected', 'cancelled'])->default('waiting');
            $table->boolean('is_settled')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_loans');
    }
};
