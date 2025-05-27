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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->references('id')->on('users')->cascadeOnDelete();
            $table->foreignIdFor(Project::class)->references('id')->on('projects')->cascadeOnDelete();
            $table->foreignIdFor(Task::class)->references('id')->on('tasks')->cascadeOnDelete();
            $table->integer('duration')->default(9);
            $table->dateTime('start_time')->nullable();
            $table->string('location_in', 100)->nullable();
            $table->string('location_lat_in', 100)->nullable();
            $table->string('location_long_in', 100)->nullable();
            $table->string('image_in', 200);
            $table->dateTime('end_time')->nullable();
            $table->string('location_out', 100)->nullable();
            $table->string('location_lat_out', 100)->nullable();
            $table->string('location_long_out', 100)->nullable();
            $table->string('image_out', 200)->nullable();
            $table->enum('status', ['absent', 'in', 'out'])->default('absent')->nullable();
            $table->boolean('is_late')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
