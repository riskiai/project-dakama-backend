<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // panjang kolom fleksibel; sesuaikan kalau perlu
            $table->string('bank_name', 100)->nullable()->after('loan');
            $table->string('account_number', 50)->nullable()->after('bank_name');

            // OPSIONAL: kalau mau cegah duplikasi nomor rekening, aktifkan unique:
            // $table->unique('account_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // kalau tadi pakai unique, drop index dulu
            // $table->dropUnique(['account_number']);

            $table->dropColumn(['bank_name', 'account_number']);
        });
    }
};

