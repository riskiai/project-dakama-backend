<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('operational_hours', function (Blueprint $table) {
            $table->integer('duration')->nullable()->default(null)->change();
            $table->integer('bonus')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('operational_hours', function (Blueprint $table) {
            $table->integer('duration')->default(0)->nullable(false)->change();
            $table->integer('bonus')->default(0)->nullable(false)->change();
        });
    }
};
