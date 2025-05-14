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
        Schema::create('projects', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->foreignId('company_id')->nullable()
            ->constrained('companies')->nullOnDelete();

            $table->foreignId('user_id')->nullable()
            ->constrained('users')->nullOnDelete();

            $table->string('name')->nullable();
            $table->string('billing')->nullable();
            $table->string('cost_estimate')->nullable();
            $table->string('margin')->nullable();
            $table->string('percent')->nullable();
            $table->string('status_cost_progres')->nullable();
            $table->string('file')->nullable();
            $table->string('date')->nullable();
            $table->string('request_status_owner')->nullable();
            $table->string('status_bonus_project')->nullable();
            $table->string('type_projects')->nullable();
            $table->string('no_dokumen_project')->nullable();
            $table->string('file_pembayaran_termin')->nullable();
            $table->string('deskripsi_termin_proyek')->nullable();
            $table->string('type_termin_proyek')->nullable();
            $table->string('harga_termin_proyek')->nullable();
            $table->date('payment_date_termin_proyek')->nullable();

            $table->timestamps();
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
