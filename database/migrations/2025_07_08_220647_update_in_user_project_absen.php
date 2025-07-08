<?php

use App\Models\ProjectHasLocation;
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
        Schema::table('users_project_absen', function (Blueprint $table) {
            $table->dropColumn([
                'longitude',
                'latitude',
                'radius',
                'status',
                'jam_masuk',
                'jam_pulang',
                'keterangan',
            ]);

            $table->foreignIdFor(ProjectHasLocation::class, 'location_id')->nullable()->references('id')->on('project_has_locations')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_project_absen', function (Blueprint $table) {
            $table->string('longitude')->nullable();
            $table->string('latitude')->nullable();
            $table->string('radius')->nullable();
            $table->string('status')->nullable();
            $table->dateTime('jam_masuk')->nullable();
            $table->dateTime('jam_pulang')->nullable();
            $table->string('keterangan')->nullable();

            $table->dropForeignIdFor(ProjectHasLocation::class, 'location_id');
        });
    }
};
