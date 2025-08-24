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
        Schema::table('attendances', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('attendance_adjustments', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('operational_hours', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('notification_recipients', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('overtimes', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('project_has_locations', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('employee_loans', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('attendance_adjustments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('operational_hours', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('notification_recipients', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('project_has_locations', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('employee_loans', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
