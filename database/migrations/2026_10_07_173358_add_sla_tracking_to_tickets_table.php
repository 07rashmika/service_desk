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
        Schema::table('tickets', function (Blueprint $table) {
            // When the ticket started waiting on the requester; the deadline moves later by that time when it resumes.
            $table->timestamp('sla_paused_at')->nullable()->after('due_at');
            // When the "due soon" and "overdue" alerts were sent, so each goes out once per deadline.
            $table->timestamp('sla_warning_sent_at')->nullable()->after('sla_paused_at');
            $table->timestamp('sla_breach_sent_at')->nullable()->after('sla_warning_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['sla_paused_at', 'sla_warning_sent_at', 'sla_breach_sent_at']);
        });
    }
};
