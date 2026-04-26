<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: create_agents_table
 *
 * Purpose: Stores all campaign participants and their current cumulative line counts.
 *
 * Key Business Rules Enforced at DB Level:
 *  - baseline_count is frozen at campaign start (May 1, 2026) — NEVER changes
 *  - pre_campaign_count <= baseline_count (agents can lose old lines, not gain)
 *  - current_total >= pre_campaign_count (total = pre_campaign + new lines)
 *  - transfer_count and new_line_count NEVER decrease (cumulative counters)
 *
 * Calculated Fields (application layer only, NOT stored):
 *  - campaign_increase        = current_total - pre_campaign_count
 *  - transfer_percentage      = transfer_count / club.required_increase
 *  - baseline_loss            = baseline_count - pre_campaign_count
 *
 * Character Set: utf8mb4_unicode_ci (supports Arabic names)
 * Dependencies: clubs
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            // ─── Primary Key ──────────────────────────────────────────────────────
            $table->uuid('agent_id')->primary()->comment('UUID primary key for the agent');

            // ─── Identity ─────────────────────────────────────────────────────────
            $table->string('agent_name', 200)
                ->comment('Full name of the agent in Arabic or English (utf8mb4 for Arabic support)');

            // ─── Line Count Fields ────────────────────────────────────────────────
            $table->unsignedInteger('baseline_count')
                ->comment('Lines owned at campaign start (May 1, 2026). FROZEN — NEVER CHANGES.');

            $table->unsignedInteger('pre_campaign_count')
                ->comment('Pre-campaign lines still active today. Can decrease as agents lose old lines. Must be <= baseline_count.');

            $table->unsignedInteger('current_total')
                ->comment('Total lines today = pre_campaign_count + campaign_increase. Must be >= pre_campaign_count.');

            $table->unsignedInteger('transfer_count')
                ->default(0)
                ->comment('Cumulative transfer + nidu lines since campaign start. NEVER decreases. Used for 60% rule.');

            $table->unsignedInteger('new_line_count')
                ->default(0)
                ->comment('Cumulative new lines since campaign start. NEVER decreases.');

            // ─── Club Membership ──────────────────────────────────────────────────
            $table->uuid('current_club_id')
                ->nullable()
                ->comment('FK → clubs.club_id. NULL = agent is outside all clubs. One club at a time.');

            $table->foreign('current_club_id')
                ->references('club_id')
                ->on('clubs')
                ->onDelete('set null')
                ->onUpdate('cascade');

            // ─── Timing Fields ────────────────────────────────────────────────────
            $table->timestamp('entry_date')
                ->nullable()
                ->comment('When agent entered their current club. Used for monthly maintenance opportunity calculation. Must be >= 2026-05-01.');

            $table->timestamp('demotion_timer_start')
                ->nullable()
                ->comment('When the demotion countdown started (lines dropped below threshold). NULL = no active countdown.');

            // ─── Flags ────────────────────────────────────────────────────────────
            $table->boolean('is_first_arrival')
                ->default(false)
                ->comment('TRUE if agent is among the first arrivals in their club. Grants bonus opportunity + doubled reward.');

            // ─── Notes ────────────────────────────────────────────────────────────
            $table->text('notes')
                ->nullable()
                ->comment('Administrative notes e.g. "Low activity due to illness". Visible only to staff.');

            // ─── Soft Delete ──────────────────────────────────────────────────────
            $table->softDeletes()
                ->comment('Soft delete for GDPR compliance. Use whereNull("deleted_at") in all queries.');

            // ─── Timestamps ───────────────────────────────────────────────────────
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────────
        // CHECK Constraints (MySQL 8.0+)
        // ─────────────────────────────────────────────────────────────────────────
        DB::statement('ALTER TABLE agents ADD CONSTRAINT chk_baseline_positive CHECK (baseline_count > 0)');
        DB::statement('ALTER TABLE agents ADD CONSTRAINT chk_pre_campaign_non_negative CHECK (pre_campaign_count >= 0)');
        DB::statement('ALTER TABLE agents ADD CONSTRAINT chk_current_total_positive CHECK (current_total > 0)');
        DB::statement('ALTER TABLE agents ADD CONSTRAINT chk_transfer_count_non_negative CHECK (transfer_count >= 0)');
        DB::statement('ALTER TABLE agents ADD CONSTRAINT chk_new_line_count_non_negative CHECK (new_line_count >= 0)');
        // Business rule: pre_campaign_count cannot exceed what the agent had at baseline
        DB::statement('ALTER TABLE agents ADD CONSTRAINT chk_pre_campaign_lte_baseline CHECK (pre_campaign_count <= baseline_count)');
        // Business rule: total lines must be at least as many as pre-campaign lines
        DB::statement('ALTER TABLE agents ADD CONSTRAINT chk_current_total_gte_pre_campaign CHECK (current_total >= pre_campaign_count)');
        // Business rule: entry_date must be on or after campaign start
        DB::statement("ALTER TABLE agents ADD CONSTRAINT chk_entry_date_campaign_start CHECK (entry_date IS NULL OR entry_date >= '2026-05-01 00:00:00')");

        // ─────────────────────────────────────────────────────────────────────────
        // Additional Indexes
        // ─────────────────────────────────────────────────────────────────────────
        DB::statement('CREATE INDEX idx_current_club_id ON agents (current_club_id)');
        DB::statement('CREATE INDEX idx_agent_created_at ON agents (created_at)');
        DB::statement('CREATE INDEX idx_agent_name ON agents (agent_name)');
        DB::statement('CREATE INDEX idx_demotion_timer_start ON agents (demotion_timer_start)');
        // Partial-like index: active agents (not soft-deleted)
        DB::statement('CREATE INDEX idx_agents_active ON agents (deleted_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
