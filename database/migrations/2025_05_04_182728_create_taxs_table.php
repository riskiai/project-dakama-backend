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
        Schema::create('taxs', function (Blueprint $table) {
            $table->id(); // bigint unsigned + auto_increment
            $table->string('name'); // varchar(255) NOT NULL
            $table->text('description'); // text NOT NULL
            $table->string('percent'); // varchar(255) NOT NULL
            $table->string('type'); // varchar(255) NOT NULL
            $table->timestamps(); // created_at & updated_at
            $table->softDeletes(); // deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxs');
    }
};
