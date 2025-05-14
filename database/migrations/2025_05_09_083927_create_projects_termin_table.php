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
        Schema::create('projects_termin', function (Blueprint $table) {
            $table->id();
            $table->string('project_id'); // Sesuaikan dengan tipe string
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->string('harga_termin')->nullable();
            $table->string('deskripsi_termin')->nullable();
            $table->string('type_termin')->nullable();
            $table->string('file_attachment_pembayaran')->nullable();
            $table->date('tanggal_payment')->nullable();
            
            $table->timestamps();
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_termin');
    }
};
