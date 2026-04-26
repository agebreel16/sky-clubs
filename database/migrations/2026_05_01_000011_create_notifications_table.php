<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: create_notifications_table
 *
 * Purpose: Tracks all system notifications sent to agents with read/unread tracking.
 *
 * Notification Types:
 *  - milestone   : Progress toward a club milestone
 *  - progress    : General progress update
 *  - achievement : Special achievement reached
 *  - promotion   : Agent was promoted to a higher club
 *  - demotion    : Agent was demoted or on warning
 *  - warning     : Demotion countdown started
 *
 * Categories:
 *  - outside_clubs: Agent has not joined any club. Has stages:
 *      on_starting_line → in_progress → on_doors
 *  - in_club: Agent is in an active club. No stage.
 *
 * Character Set: utf8mb4_unicode_ci (supports Arabic notification text)
 * Dependencies: agents, clubs
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            // ─── Primary Key ──────────────────────────────────────────────────────
            $table->uuid('notification_id')->primary()->comment('UUID primary key');

            // ─── Agent Reference ──────────────────────────────────────────────────
            $table->uuid('agent_id')
                ->comment('FK → agents.agent_id. CASCADE on delete.');

            $table->foreign('agent_id')
                ->references('agent_id')
                ->on('agents')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // ─── Notification Content ─────────────────────────────────────────────
            $table->enum('notification_type', ['milestone', 'progress', 'achievement', 'promotion', 'demotion', 'warning'])
                ->comment('Category of notification for icon/styling on frontend');

            $table->string('title', 200)
                ->comment('Short notification title e.g. "On the Doors!", "Promoted to Excellence Club!"');

            $table->text('body')
                ->comment('Full notification message body (Arabic or English)');

            // ─── Context Classification ───────────────────────────────────────────
            $table->enum('category', ['outside_clubs', 'in_club'])
                ->comment('outside_clubs=agent not yet in club|in_club=agent is active club member');

            $table->string('stage', 50)
                ->nullable()
                ->comment('Progress stage for outside_clubs: on_starting_line|in_progress|on_doors. NULL for in_club.');

            // ─── Progress Data ────────────────────────────────────────────────────
            $table->unsignedInteger('current_count')
                ->nullable()
                ->comment('Agent\'s line count when notification was generated (for context display)');

            $table->unsignedInteger('required_count')
                ->nullable()
                ->comment('Lines needed to reach next milestone (for progress bar display)');

            // ─── Club Reference ───────────────────────────────────────────────────
            $table->uuid('club_id')
                ->nullable()
                ->comment('FK → clubs.club_id. Associated club. NULL for general notifications. SET NULL if club deleted.');

            $table->foreign('club_id')
                ->references('club_id')
                ->on('clubs')
                ->onDelete('set null')
                ->onUpdate('cascade');

            // ─── Read Status ──────────────────────────────────────────────────────
            $table->boolean('is_read')
                ->default(false)
                ->comment('FALSE = unread (shows badge). TRUE = agent has opened/viewed this notification.');

            $table->timestamp('sent_at')
                ->comment('UTC timestamp when notification was dispatched to agent.');

            $table->timestamp('read_at')
                ->nullable()
                ->comment('UTC timestamp when agent viewed the notification. NULL = unread.');

            // ─── Soft Delete ──────────────────────────────────────────────────────
            $table->softDeletes()
                ->comment('Soft delete. Deleted notifications hidden from agent view but retained for audit.');

            // ─── Timestamps ───────────────────────────────────────────────────────
            $table->timestamp('created_at')->useCurrent()->comment('Record creation timestamp (UTC)');
        });

        // ─────────────────────────────────────────────────────────────────────────
        // Additional Indexes
        // ─────────────────────────────────────────────────────────────────────────
        DB::statement('CREATE INDEX idx_notif_agent_id ON notifications (agent_id)');
        DB::statement('CREATE INDEX idx_notif_type ON notifications (notification_type)');
        DB::statement('CREATE INDEX idx_notif_sent_at ON notifications (sent_at)');
        DB::statement('CREATE INDEX idx_notif_is_read ON notifications (is_read)');
        DB::statement('CREATE INDEX idx_notif_club_id ON notifications (club_id)');
        // Composite: unread notifications per agent (badge count query)
        DB::statement('CREATE INDEX idx_notif_agent_is_read ON notifications (agent_id, is_read)');
        // Composite: agent notifications sorted by date (notification list query)
        DB::statement('CREATE INDEX idx_notif_agent_sent ON notifications (agent_id, sent_at DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
