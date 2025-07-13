<?php

use App\Models\Notification;
use App\Models\Role;
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
        Schema::create('notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Notification::class)->references('id')->on('notifications')->cascadeOnDelete();
            $table->foreignIdFor(User::class)->nullable();
            $table->foreignIdFor(Role::class)->nullable()->references('id')->on('roles')->cascadeOnDelete();
            $table->string('read_by')->nullable();
            $table->timestamp('read_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_recipients');
    }
};
