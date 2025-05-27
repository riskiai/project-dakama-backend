<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
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
        Schema::create('overtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->references('id')->on('users')->cascadeOnDelete();
            $table->foreignIdFor(Project::class)->references('id')->on('projects')->cascadeOnDelete();
            $table->foreignIdFor(Task::class)->references('id')->on('tasks')->cascadeOnDelete();
            $table->integer('duration')->default(1);
            $table->dateTime('request_date');
            $table->string('reason')->default('-');
            $table->enum('status', ['waiting', 'approved', 'rejected', 'cancelled'])->default('waiting');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtimes');
    }
};
