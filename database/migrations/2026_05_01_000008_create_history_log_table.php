<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: create_history_log_table
 *
 * ⚠️  IMMUTABLE AUDIT LOG — APPEND-ONLY ⚠️
 *
 * Purpose: Records every significant campaign event for each agent.
 * This table MUST be append-only: no UPDATE or DELETE from application code.
 *
 * Event Types:
 *  - promotion  : Agent moved up to a higher club
 *  - demotion   : Agent moved down to a lower club (or removed from clubs)
 *  - warning    : Demotion countdown timer started
 *  - achievement: Special milestone reached (e.g. first_arrival status)
 *  - data_import: Excel/API data was imported and processed
 *
 * This table deliberately has NO soft_deletes — it must be immutable.
 * Data corrections require direct DB access with managerial approval only.
 *
 * Character Set: utf8mb4_unicode_ci
 * Dependencies: agents, clubs
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('history_logs', function (Blueprint $table) {
            // ─── Primary Key ──────────────────────────────────────────────────────
            $table->uuid('log_id')->primary()->comment('UUID primary key. Append-only — never deleted.');

            // ─── Agent Reference ──────────────────────────────────────────────────
            $table->uuid('agent_id')
                ->comment('FK → agents.agent_id. CASCADE on delete (if agent removed, history removed).');

            $table->foreign('agent_id')
                ->references('agent_id')
                ->on('agents')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // ─── Event Classification ─────────────────────────────────────────────
            $table->enum('event_type', ['promotion', 'demotion', 'warning', 'achievement', 'data_import'])
                ->comment('Type of campaign event: promotion|demotion|warning|achievement|data_import');

            // ─── Club Movement ────────────────────────────────────────────────────
            $table->uuid('from_club_id')
                ->nullable()
                ->comment('FK → clubs.club_id. Club agent came FROM. NULL = entered from outside clubs.');

            $table->foreign('from_club_id')
                ->references('club_id')
                ->on('clubs')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->uuid('to_club_id')
                ->nullable()
                ->comment('FK → clubs.club_id. Club agent went TO. NULL = removed from clubs or non-club event.');

            $table->foreign('to_club_id')
                ->references('club_id')
                ->on('clubs')
                ->onDelete('set null')
                ->onUpdate('cascade');

            // ─── Event Details ────────────────────────────────────────────────────
            $table->string('reason', 500)
                ->nullable()
                ->comment('Human-readable reason e.g. "Reached 25 lines", "Fell below required threshold"');

            $table->json('metadata')
                ->nullable()
                ->comment('Flexible JSON data e.g. {"lines_before":24,"lines_after":25,"transfer_pct":0.68}');

            // ─── Timing ───────────────────────────────────────────────────────────
            $table->timestamp('event_timestamp')
                ->comment('UTC timestamp when the event actually occurred (may differ from created_at)');

            // ─── Timestamps ───────────────────────────────────────────────────────
            // NOTE: Only created_at. No updated_at — this is append-only.
            // NOTE: NO softDeletes — this table must be immutable.
            $table->timestamp('created_at')->useCurrent()->comment('Record insertion timestamp (UTC). Immutable.');
        });

        // ─────────────────────────────────────────────────────────────────────────
        // Additional Indexes
        // ─────────────────────────────────────────────────────────────────────────
        DB::statement('CREATE INDEX idx_hist_agent_id ON history_logs (agent_id)');
        DB::statement('CREATE INDEX idx_hist_event_type ON history_logs (event_type)');
        DB::statement('CREATE INDEX idx_hist_event_timestamp ON history_logs (event_timestamp)');
        DB::statement('CREATE INDEX idx_hist_from_club ON history_logs (from_club_id)');
        DB::statement('CREATE INDEX idx_hist_to_club ON history_logs (to_club_id)');
        // Composite: all events for an agent filtered by type (agent timeline view)
        DB::statement('CREATE INDEX idx_hist_agent_event ON history_logs (agent_id, event_type)');

        // ─────────────────────────────────────────────────────────────────────────
        // DB-Level Trigger: Prevent UPDATE and DELETE from application
        // These triggers enforce immutability at the database layer.
        // ─────────────────────────────────────────────────────────────────────────
        DB::unprepared('
            CREATE TRIGGER prevent_history_log_update
            BEFORE UPDATE ON history_logs
            FOR EACH ROW
            SIGNAL SQLSTATE "45000"
            SET MESSAGE_TEXT = "IMMUTABLE TABLE: Updates to history_log are not permitted. Contact DBA for corrections.";
        ');

        DB::unprepared('
            CREATE TRIGGER prevent_history_log_delete
            BEFORE DELETE ON history_logs
            FOR EACH ROW
            SIGNAL SQLSTATE "45000"
            SET MESSAGE_TEXT = "IMMUTABLE TABLE: Deletes from history_log are not permitted. Contact DBA for corrections.";
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS prevent_history_log_update');
        DB::unprepared('DROP TRIGGER IF EXISTS prevent_history_log_delete');
        Schema::dropIfExists('history_logs');
    }
};
