<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: create_rewards_table
 *
 * Purpose: Tracks all monetary rewards given to agents in NIS (New Israeli Shekels).
 *
 * Reward Types (determined by is_first_arrival flag):
 *  Standard Entry Rewards:
 *   - Launch Club:     300 NIS
 *   - Excellence Club: 700 NIS
 *   - Peak Club:      1000 NIS
 *
 *  First-Arrival Bonus Rewards (is_first_arrival = true):
 *   - Launch Club:     600 NIS  (10 first arrivals)
 *   - Excellence Club: 1500 NIS (5 first arrivals)
 *   - Peak Club:      2000 NIS  (5 first arrivals)
 *
 * Payment Workflow: pending → paid | failed
 *
 * Character Set: utf8mb4_unicode_ci
 * Dependencies: agents, clubs
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rewards', function (Blueprint $table) {
            // ─── Primary Key ──────────────────────────────────────────────────────
            $table->uuid('reward_id')->primary()->comment('UUID primary key');

            // ─── Agent & Club References ──────────────────────────────────────────
            $table->uuid('agent_id')
                ->comment('FK → agents.agent_id. CASCADE on delete.');

            $table->foreign('agent_id')
                ->references('agent_id')
                ->on('agents')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->uuid('club_id')
                ->comment('FK → clubs.club_id. Club for which this reward was issued. CASCADE on delete.');

            $table->foreign('club_id')
                ->references('club_id')
                ->on('clubs')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // ─── Financial Data ───────────────────────────────────────────────────
            $table->decimal('amount', 10, 2)
                ->comment('Reward amount in NIS. Base: 300|700|1000. First-arrival: 600|1500|2000.');

            $table->boolean('is_first_arrival')
                ->default(false)
                ->comment('TRUE = first-arrival bonus reward. FALSE = standard entry reward.');

            // ─── Payment Status ───────────────────────────────────────────────────
            $table->enum('payment_status', ['pending', 'paid', 'failed'])
                ->default('pending')
                ->comment('pending=awaiting payment|paid=transferred|failed=payment error');

            $table->timestamp('paid_date')
                ->nullable()
                ->comment('UTC timestamp when payment was confirmed. NULL unless payment_status = "paid".');

            // ─── Soft Delete ──────────────────────────────────────────────────────
            $table->softDeletes()
                ->comment('Soft delete for audit trails. Hard delete not permitted in production.');

            // ─── Timestamps ───────────────────────────────────────────────────────
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────────
        // CHECK Constraints (MySQL 8.0+)
        // ─────────────────────────────────────────────────────────────────────────
        DB::statement('ALTER TABLE rewards ADD CONSTRAINT chk_reward_amount_non_negative CHECK (amount >= 0)');

        // ─────────────────────────────────────────────────────────────────────────
        // Additional Indexes
        // ─────────────────────────────────────────────────────────────────────────
        DB::statement('CREATE INDEX idx_reward_agent_id ON rewards (agent_id)');
        DB::statement('CREATE INDEX idx_reward_club_id ON rewards (club_id)');
        DB::statement('CREATE INDEX idx_payment_status ON rewards (payment_status)');
        DB::statement('CREATE INDEX idx_paid_date ON rewards (paid_date)');
        // Composite: all rewards for an agent filtered by status (finance officer view)
        DB::statement('CREATE INDEX idx_reward_agent_status ON rewards (agent_id, payment_status)');
    }

    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
