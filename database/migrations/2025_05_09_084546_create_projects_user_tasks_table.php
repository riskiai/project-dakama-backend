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
        Schema::create('projects_user_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('project_id');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable();  // Menjadikan user_id nullable
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('tasks_id')->nullable();  // Menjadikan user_id nullable
            $table->foreign('tasks_id')->references('id')->on('tasks')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_user_tasks');
    }
};
