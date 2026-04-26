<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: create_opportunities_table
 *
 * Purpose: Tracks all lottery ticket opportunities earned by agents.
 * Each row represents ONE lottery ticket for a specific club draw.
 *
 * Opportunity Types:
 *  - entry        : Earned on club entry (cumulative: 1 for Launch, 2 for Excellence, 3 for Peak)
 *  - maintenance  : Earned monthly while maintaining club membership (preserved on demotion)
 *  - bonus        : Earned per 20 lines in Peak Club only (has_bonus_opportunities = true)
 *  - first_arrival: Bonus ticket for first-arrival agents (doubled reward + extra ticket)
 *
 * Important: is_active = FALSE when opportunity is cancelled due to demotion.
 *
 * Character Set: utf8mb4_unicode_ci
 * Dependencies: agents, clubs
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            // ─── Primary Key ──────────────────────────────────────────────────────
            $table->uuid('opportunity_id')->primary()->comment('UUID primary key');

            // ─── Agent & Club References ──────────────────────────────────────────
            $table->uuid('agent_id')
                ->comment('FK → agents.agent_id. CASCADE on delete.');

            $table->foreign('agent_id')
                ->references('agent_id')
                ->on('agents')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->uuid('club_id')
                ->comment('FK → clubs.club_id. The club lottery this ticket applies to. CASCADE on delete.');

            $table->foreign('club_id')
                ->references('club_id')
                ->on('clubs')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // ─── Opportunity Classification ───────────────────────────────────────
            $table->enum('type', ['entry', 'maintenance', 'bonus', 'first_arrival'])
                ->comment('entry=club join|maintenance=monthly|bonus=Peak extra lines|first_arrival=top arrivals bonus');

            // ─── Timing ───────────────────────────────────────────────────────────
            $table->timestamp('earned_date')
                ->comment('UTC timestamp when this opportunity was earned');

            // ─── Status ───────────────────────────────────────────────────────────
            $table->boolean('is_active')
                ->default(true)
                ->comment('FALSE if cancelled (e.g. agent was demoted). Cancelled tickets excluded from draw.');

            $table->string('cancellation_reason', 500)
                ->nullable()
                ->comment('Why this ticket was cancelled e.g. "Demoted to Launch Club on 2026-08-15"');

            // ─── Soft Delete ──────────────────────────────────────────────────────
            $table->softDeletes()
                ->comment('Soft delete support. Deleted opportunities excluded from draws.');

            // ─── Timestamps ───────────────────────────────────────────────────────
            $table->timestamp('created_at')->useCurrent()->comment('Record creation timestamp (UTC)');
        });

        // ─────────────────────────────────────────────────────────────────────────
        // Additional Indexes
        // ─────────────────────────────────────────────────────────────────────────
        DB::statement('CREATE INDEX idx_opp_agent_id ON opportunities (agent_id)');
        DB::statement('CREATE INDEX idx_opp_club_id ON opportunities (club_id)');
        DB::statement('CREATE INDEX idx_opp_type ON opportunities (type)');
        DB::statement('CREATE INDEX idx_opp_earned_date ON opportunities (earned_date)');
        DB::statement('CREATE INDEX idx_opp_is_active ON opportunities (is_active)');
        // Composite: all tickets for an agent in a specific club
        DB::statement('CREATE INDEX idx_opp_agent_club ON opportunities (agent_id, club_id)');
        // Composite: active tickets per agent (used in draw eligibility checks)
        DB::statement('CREATE INDEX idx_opp_agent_is_active ON opportunities (agent_id, is_active)');
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
