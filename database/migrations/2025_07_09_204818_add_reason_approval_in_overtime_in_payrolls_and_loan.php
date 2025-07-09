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
        Schema::table('payrolls', function (Blueprint $table) {
            $table->string('reason_approval')->nullable()->after('status');
        });

        Schema::table('overtimes', function (Blueprint $table) {
            $table->string('reason_approval')->nullable()->after('status');
        });

        Schema::table('employee_loans', function (Blueprint $table) {
            $table->string('reason_approval')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->string('reason_approval')->nullable()->after('status');
        });

        Schema::table('overtimes', function (Blueprint $table) {
            $table->string('reason_approval')->nullable()->after('status');
        });

        Schema::table('employee_loans', function (Blueprint $table) {
            $table->string('reason_approval')->nullable()->after('status');
        });
    }
};
