<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: create_audit_logs_table
 *
 * ⚠️  INTEGRITY-CRITICAL — APPEND-ONLY ⚠️
 *
 * Purpose: Complete immutable audit trail of ALL administrative actions.
 * Records who did what, when, from where, and whether it succeeded.
 *
 * Tracked Actions:
 *  create, read, update, delete, export, import, rollback, login, failed_login
 *
 * Tracked Models:
 *  Agent, Club, DataImport, User, Reward
 *
 * Retention: Minimum 3 years (regulatory requirement).
 * No UPDATE or DELETE from application code. Triggers enforce this.
 *
 * Character Set: utf8mb4_unicode_ci
 * Dependencies: users
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            // ─── Primary Key ──────────────────────────────────────────────────────
            $table->uuid('audit_id')->primary()->comment('UUID primary key. Append-only — immutable.');

            // ─── Operator Reference ───────────────────────────────────────────────
            $table->uuid('user_id')
                ->nullable()
                ->comment('FK → users.id. Who performed this action. NULL = system-initiated action.');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null')
                ->onUpdate('cascade');

            // ─── Action Classification ────────────────────────────────────────────
            $table->string('action', 100)
                ->comment('Action type: create|read|update|delete|export|import|rollback|login|failed_login');

            $table->string('model_type', 100)
                ->comment('Entity type affected: Agent|Club|DataImport|User|Reward');

            $table->uuid('model_id')
                ->comment('UUID of the specific entity affected by this action');

            // ─── Change Data Capture ──────────────────────────────────────────────
            $table->json('old_values')
                ->nullable()
                ->comment('Previous state before the change e.g. {"current_count":100,"club_id":"abc"}');

            $table->json('new_values')
                ->nullable()
                ->comment('New state after the change e.g. {"current_count":125,"club_id":"def"}');

            // ─── Request Context ──────────────────────────────────────────────────
            $table->string('ip_address', 45)
                ->comment('IPv4 or IPv6 address of the request. 45 chars supports full IPv6 notation.');

            $table->text('user_agent')
                ->nullable()
                ->comment('Browser/application user-agent string for device tracking and fraud detection.');

            // ─── Human-Readable Description ───────────────────────────────────────
            $table->text('description')
                ->nullable()
                ->comment('Plain-language description e.g. "Agent promoted from Launch Club to Excellence Club"');

            // ─── Result ───────────────────────────────────────────────────────────
            $table->enum('status', ['success', 'failure'])
                ->comment('Whether the action completed successfully or failed');

            $table->text('error_message')
                ->nullable()
                ->comment('Error details if status = "failure". NULL on success.');

            // ─── Timestamps ───────────────────────────────────────────────────────
            // NOTE: Only created_at. No updated_at — immutable by design.
            // NOTE: NO softDeletes — audit logs must never be hidden.
            $table->timestamp('created_at')->useCurrent()->comment('Action timestamp (UTC). Immutable.');
        });

        // ─────────────────────────────────────────────────────────────────────────
        // Additional Indexes
        // ─────────────────────────────────────────────────────────────────────────
        DB::statement('CREATE INDEX idx_audit_user_id ON audit_logs (user_id)');
        DB::statement('CREATE INDEX idx_audit_model_type ON audit_logs (model_type)');
        DB::statement('CREATE INDEX idx_audit_model_id ON audit_logs (model_id)');
        DB::statement('CREATE INDEX idx_audit_action ON audit_logs (action)');
        DB::statement('CREATE INDEX idx_audit_created_at ON audit_logs (created_at)');
        // Composite: all actions by a user sorted by time (user activity report)
        DB::statement('CREATE INDEX idx_audit_user_created ON audit_logs (user_id, created_at DESC)');
        // Composite: all actions on a specific model type + action (e.g. all failed logins)
        DB::statement('CREATE INDEX idx_audit_model_action ON audit_logs (model_type, action)');

        // ─────────────────────────────────────────────────────────────────────────
        // DB-Level Triggers: Enforce Immutability
        // ─────────────────────────────────────────────────────────────────────────
        DB::unprepared('
            CREATE TRIGGER prevent_audit_logs_update
            BEFORE UPDATE ON audit_logs
            FOR EACH ROW
            SIGNAL SQLSTATE "45000"
            SET MESSAGE_TEXT = "IMMUTABLE TABLE: Updates to audit_logs are strictly prohibited. Contact DBA.";
        ');

        DB::unprepared('
            CREATE TRIGGER prevent_audit_logs_delete
            BEFORE DELETE ON audit_logs
            FOR EACH ROW
            SIGNAL SQLSTATE "45000"
            SET MESSAGE_TEXT = "IMMUTABLE TABLE: Deletes from audit_logs are strictly prohibited. Contact DBA.";
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS prevent_audit_logs_update');
        DB::unprepared('DROP TRIGGER IF EXISTS prevent_audit_logs_delete');
        Schema::dropIfExists('audit_logs');
    }
};
