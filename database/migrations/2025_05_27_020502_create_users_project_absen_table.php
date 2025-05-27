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
        Schema::create('users_project_absen', function (Blueprint $table) {
            $table->id();
            // Relasi user nullable + nullOnDelete
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Relasi project nullable + nullOnDelete
            $table->string('project_id')->nullable();
            $table->foreign('project_id')
                ->references('id')->on('projects')
                ->nullOnDelete();

            // Kolom tambahan (semua string sesuai permintaan)
            $table->string('longitude')->nullable();
            $table->string('latitude')->nullable();
            $table->string('radius')->nullable();
            $table->string('status')->nullable(); 
            $table->dateTime('jam_masuk')->nullable();
            $table->dateTime('jam_pulang')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_project_absen');
    }
};
