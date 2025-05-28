<?php

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
        Schema::create('mutation_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->references('id')->on('users');
            $table->morphs('mutable');
            $table->integer('increase')->default(0);
            $table->integer('decrease')->default(0);
            $table->integer('latest')->default(0);
            $table->integer('total')->default(0);
            $table->string('description', 150)->default('-');
            $table->foreignIdFor(User::class, 'created_by')->nullable()->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutation_loans');
    }
};
