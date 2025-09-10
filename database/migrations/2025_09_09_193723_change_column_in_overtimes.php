<?php

use App\Models\Budget;
use App\Models\Task;
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
        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropForeign(['task_id']);
            $table->dropColumn('task_id');

            $table->foreignIdFor(Budget::class)->references('id')->on('budgets');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            $table->foreignIdFor(Task::class)->references('id')->on('tasks');
            $table->dropForeign(['budget_id']);
            $table->dropColumn('budget_id');
        });
    }
};
