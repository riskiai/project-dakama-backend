<?php

use App\Models\Overtime;
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
            $table->tinyInteger('type')->default(0)->comment('0 = kehadiran, 1 = lembur');
            $table->foreignIdFor(Overtime::class)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropForeignIdFor(Overtime::class);
        });
    }
};
