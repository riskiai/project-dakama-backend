<?php

use App\Models\Project;
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
        Schema::create('project_has_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Project::class)->references('id')->on('projects')->cascadeOnDelete();
            $table->string('longitude', 50);
            $table->string('latitude', 50);
            $table->integer('radius')->default(6);
            $table->string('name', 100);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_has_locations');
    }
};
