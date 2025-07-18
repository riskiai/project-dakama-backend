<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            // tambahkan di akhir kolom agar mudah dibaca; sesuaikan posisi kalau perlu
            $table->string('unit')->nullable()->after('nominal');
            $table->string('stok')->nullable()->after('unit');
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropColumn(['unit', 'stok']);
        });
    }
};

