<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Tambah kolom FK (nullable agar tidak mengganggu data lama)
            $table->unsignedBigInteger('operational_hour_id')
                  ->nullable()
                  ->after('user_id');

            // Tambah foreign key ke operational_hours(id)
            $table->foreign('operational_hour_id')
                  ->references('id')
                  ->on('operational_hours')
                  ->nullOnDelete(); // jika master dihapus, set null di projects
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Hapus dulu constraint, lalu kolomnya
            $table->dropForeign(['operational_hour_id']);
            $table->dropColumn('operational_hour_id');
        });
    }
};
