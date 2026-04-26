<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: create_clubs_table
 *
 * Purpose: Stores all club configurations for the Sky Clubs Campaign.
 * This is the DYNAMIC CONTROL CENTER - any change applies immediately to all agents.
 *
 * Clubs:
 *  - Launch Club    (order=1): 25 lines required, 300 NIS reward
 *  - Excellence Club(order=2): 50 lines required, 700 NIS reward
 *  - Peak Club      (order=3): 100 lines required, 1000 NIS reward
 *
 * Character Set: utf8mb4_unicode_ci (supports Arabic)
 * Engine: InnoDB (for FK support and ACID compliance)
 * Dependencies: None (this is the root table)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the `clubs` table with all constraints, indexes, and CHECK constraints.
     */
    public function up(): void
    {
        Schema::create('clubs', function (Blueprint $table) {
            // ─── Primary Key ──────────────────────────────────────────────────────
            $table->uuid('club_id')->primary()->comment('UUID primary key for the club');

            // ─── Identity & Ordering ──────────────────────────────────────────────
            $table->string('club_name', 100)
                ->unique()
                ->comment('Club name (Launch Club, Excellence Club, Peak Club) — NEVER translate');

            $table->unsignedInteger('club_order')
                ->unique()
                ->comment('Display order: 1=Launch, 2=Excellence, 3=Peak. Must be > 0');

            // ─── Qualification Thresholds ────────────────────────────────────────
            $table->unsignedInteger('required_increase')
                ->comment('New lines needed to enter this club: 25, 50, 100');

            $table->unsignedInteger('required_transfer_count')
                ->comment('Minimum transfer/nidu lines needed: 15, 30, 60');

            $table->decimal('required_transfer_percentage', 3, 2)
                ->default(0.60)
                ->comment('Minimum transfer ratio (0.00–1.00). Fixed at 0.60 (60% rule)');

            // ─── Reward Configuration ─────────────────────────────────────────────
            $table->decimal('base_reward_amount', 10, 2)
                ->comment('Entry bonus in NIS: 300, 700, 1000');

            $table->decimal('first_arrival_reward_amount', 10, 2)
                ->comment('First-arrival bonus in NIS: 600, 1500, 2000');

            $table->unsignedInteger('first_arrival_count')
                ->comment('Number of first-arrival slots per club: 10, 5, 5');

            // ─── Lottery Configuration ────────────────────────────────────────────
            $table->unsignedInteger('seat_capacity')
                ->comment('MINIMUM threshold of agents required to UNLOCK the lottery draw: 90, 55, 45. There is NO MAXIMUM limit (scale dynamically).');

            $table->decimal('grand_prize_amount', 12, 2)
                ->comment('Lottery grand prize in NIS: 15000, 35000, 70000');

            $table->unsignedInteger('entry_opportunities')
                ->comment('Cumulative lottery tickets earned on entry: 1, 2, 3');

            // ─── Demotion Logic ───────────────────────────────────────────────────
            $table->unsignedInteger('demotion_timer_days')
                ->default(7)
                ->comment('Days until demotion after lines drop below required threshold');

            // ─── Bonus Opportunities (Peak Club Only) ─────────────────────────────
            $table->boolean('has_bonus_opportunities')
                ->default(false)
                ->comment('TRUE only for Peak Club: extra ticket per bonus_per_numbers lines');

            $table->unsignedInteger('bonus_per_numbers')
                ->nullable()
                ->comment('Lines needed for 1 bonus opportunity (NULL unless has_bonus_opportunities=true). Peak Club = 20');

            // ─── Status ───────────────────────────────────────────────────────────
            $table->boolean('is_active')
                ->default(true)
                ->comment('Soft enable/disable of this club without deletion');

            // ─── Timestamps ───────────────────────────────────────────────────────
            $table->timestamps(); // created_at, updated_at (CURRENT_TIMESTAMP)
        });

        // ─────────────────────────────────────────────────────────────────────────
        // CHECK Constraints (MySQL 8.0+)
        // These enforce business rules at the database level as a safety net.
        // ─────────────────────────────────────────────────────────────────────────
        DB::statement('ALTER TABLE clubs ADD CONSTRAINT chk_club_order_positive CHECK (club_order > 0)');
        DB::statement('ALTER TABLE clubs ADD CONSTRAINT chk_required_increase_positive CHECK (required_increase > 0)');
        DB::statement('ALTER TABLE clubs ADD CONSTRAINT chk_required_transfer_count_positive CHECK (required_transfer_count > 0)');
        DB::statement('ALTER TABLE clubs ADD CONSTRAINT chk_transfer_percentage_range CHECK (required_transfer_percentage >= 0.00 AND required_transfer_percentage <= 1.00)');
        DB::statement('ALTER TABLE clubs ADD CONSTRAINT chk_base_reward_non_negative CHECK (base_reward_amount >= 0)');
        DB::statement('ALTER TABLE clubs ADD CONSTRAINT chk_first_arrival_reward_non_negative CHECK (first_arrival_reward_amount >= 0)');
        DB::statement('ALTER TABLE clubs ADD CONSTRAINT chk_first_arrival_count_positive CHECK (first_arrival_count > 0)');
        DB::statement('ALTER TABLE clubs ADD CONSTRAINT chk_seat_capacity_positive CHECK (seat_capacity > 0)');
        DB::statement('ALTER TABLE clubs ADD CONSTRAINT chk_grand_prize_positive CHECK (grand_prize_amount > 0)');
        DB::statement('ALTER TABLE clubs ADD CONSTRAINT chk_demotion_timer_positive CHECK (demotion_timer_days > 0)');
        DB::statement('ALTER TABLE clubs ADD CONSTRAINT chk_entry_opportunities_positive CHECK (entry_opportunities > 0)');
        DB::statement('ALTER TABLE clubs ADD CONSTRAINT chk_bonus_per_numbers_positive CHECK (bonus_per_numbers IS NULL OR bonus_per_numbers > 0)');

        // ─────────────────────────────────────────────────────────────────────────
        // Additional Indexes (beyond those already created by unique/index)
        // ─────────────────────────────────────────────────────────────────────────
        DB::statement('CREATE INDEX idx_is_active ON clubs (is_active)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};
