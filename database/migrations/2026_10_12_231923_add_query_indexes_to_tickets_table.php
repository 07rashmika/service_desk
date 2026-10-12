<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for the queries that run most: the queue tabs and SLA checks (status
     * plus deadline), "assigned to me", and the reports' date ranges.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->index(['status_id', 'due_at']);
            $table->index(['assigned_to', 'status_id']);
            $table->index('created_at');
            $table->index('resolved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // MySQL drops its automatic foreign key indexes once the composite indexes cover
        // those columns, so put them back before removing the composite ones.
        $missingForeignKeyIndexes = array_filter(['status_id', 'assigned_to'], fn (string $column): bool => ! Schema::hasIndex('tickets', [$column]));

        Schema::table('tickets', function (Blueprint $table) use ($missingForeignKeyIndexes) {
            foreach ($missingForeignKeyIndexes as $column) {
                $table->index($column);
            }

            $table->dropIndex(['status_id', 'due_at']);
            $table->dropIndex(['assigned_to', 'status_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['resolved_at']);
        });
    }
};
