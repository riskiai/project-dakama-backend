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
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['task_id']);
            $table->dropColumn('task_id');
            $table->dropColumn(['hourly_salary', 'bonus_ontime', 'transport']);

            $table->foreignIdFor(Budget::class)->references('id')->on('budgets');

            $table->enum('status', ['absent', 'in', 'out', 'permit', 'sick'])->default('absent')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignIdFor(Task::class)->references('id')->on('tasks');
            $table->integer('hourly_salary')->default(0);
            $table->integer('transport')->default(0);
            $table->integer('bonus_ontime')->default(0);

            $table->dropForeign(['budget_id']);
            $table->dropColumn('budget_id');

            $table->enum('status', ['absent', 'in', 'out'])->default('absent')->change();
        });
    }
};
