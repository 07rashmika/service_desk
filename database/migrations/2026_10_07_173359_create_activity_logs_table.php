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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            // Who did it; null when the system did (e.g. auto-close).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // What it was done to: a ticket, a user, a role, a setting.
            $table->nullableMorphs('subject');
            $table->string('event', 60)->index();
            $table->string('description');
            // Before/after values: {"old": {...}, "new": {...}}
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
