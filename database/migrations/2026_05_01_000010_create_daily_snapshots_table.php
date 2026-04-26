<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: create_daily_snapshots_table
 *
 * Purpose: Stores a point-in-time snapshot of each agent's data for every import date.
 * Enables historical comparison, anomaly detection, and rollback capability.
 *
 * Key Design Decisions:
 *  - UNIQUE (data_date, agent_id): only one snapshot per agent per day
 *  - Snapshot is immutable once written — reflects state at import time
 *  - club_id_at_date: SET NULL if club is deleted (historical reference preserved)
 *  - Used for: trend analysis, rollback to previous state, historical reports
 *
 * Size Consideration:
 *  At ~1000 agents × 365 days = ~365,000 rows per year. Ensure regular ANALYZE TABLE.
 *
 * Character Set: utf8mb4_unicode_ci
 * Dependencies: data_imports, agents, clubs
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_snapshots', function (Blueprint $table) {
            // ─── Primary Key ──────────────────────────────────────────────────────
            $table->uuid('snapshot_id')->primary()->comment('UUID primary key');

            // ─── Source Import Reference ──────────────────────────────────────────
            $table->uuid('import_id')
                ->comment('FK → data_imports.import_id. The import that created this snapshot. CASCADE on delete.');

            $table->foreign('import_id')
                ->references('import_id')
                ->on('data_imports')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // ─── Date ─────────────────────────────────────────────────────────────
            $table->date('data_date')
                ->comment('Campaign date this snapshot represents (YYYY-MM-DD)');

            // ─── Agent Reference ──────────────────────────────────────────────────
            $table->uuid('agent_id')
                ->comment('FK → agents.agent_id. CASCADE on delete.');

            $table->foreign('agent_id')
                ->references('agent_id')
                ->on('agents')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // ─── Snapshotted Line Counts ──────────────────────────────────────────
            $table->unsignedInteger('baseline_count')
                ->comment('Agent\'s baseline at snapshot time (should be constant = campaign start baseline)');

            $table->unsignedInteger('pre_campaign_count')
                ->comment('Pre-campaign lines still active on this date');

            $table->unsignedInteger('current_total')
                ->comment('Total lines on this date = pre_campaign_count + campaign_increase');

            $table->unsignedInteger('transfer_count')
                ->comment('Cumulative transfer/nidu lines on this date');

            $table->unsignedInteger('new_line_count')
                ->comment('Cumulative new lines on this date');

            // ─── Club Status at Snapshot Time ─────────────────────────────────────
            $table->uuid('club_id_at_date')
                ->nullable()
                ->comment('FK → clubs.club_id. Club agent was in on this date. NULL = outside clubs. SET NULL if club deleted.');

            $table->foreign('club_id_at_date')
                ->references('club_id')
                ->on('clubs')
                ->onDelete('set null')
                ->onUpdate('cascade');

            // ─── Timestamps ───────────────────────────────────────────────────────
            // NOTE: Only created_at. Snapshots are immutable — no updated_at.
            $table->timestamp('created_at')->useCurrent()->comment('Snapshot insertion timestamp (UTC). Immutable.');
        });

        // ─────────────────────────────────────────────────────────────────────────
        // Unique Constraint: One snapshot per agent per day
        // ─────────────────────────────────────────────────────────────────────────
        DB::statement('ALTER TABLE daily_snapshots ADD CONSTRAINT uq_date_agent UNIQUE (data_date, agent_id)');

        // ─────────────────────────────────────────────────────────────────────────
        // Additional Indexes
        // ─────────────────────────────────────────────────────────────────────────
        DB::statement('CREATE INDEX idx_snap_agent_id ON daily_snapshots (agent_id)');
        DB::statement('CREATE INDEX idx_snap_data_date ON daily_snapshots (data_date)');
        DB::statement('CREATE INDEX idx_snap_import_id ON daily_snapshots (import_id)');
        DB::statement('CREATE INDEX idx_snap_club_id ON daily_snapshots (club_id_at_date)');
        // Composite: all snapshots for an import (used in rollback operations)
        DB::statement('CREATE INDEX idx_snap_date_import ON daily_snapshots (data_date, import_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_snapshots');
    }
};
