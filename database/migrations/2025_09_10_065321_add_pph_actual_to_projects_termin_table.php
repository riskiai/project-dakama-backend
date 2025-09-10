<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects_termin', function (Blueprint $table) {
            // nilai potongan & penerimaan bersih per termin
            $table->decimal('pph', 15, 2)->nullable()->after('harga_termin');
            $table->decimal('actual_payment', 15, 2)->nullable()->after('pph');
        });
    }

    public function down(): void
    {
        Schema::table('projects_termin', function (Blueprint $table) {
            $table->dropColumn(['pph', 'actual_payment']);
        });
    }
};
