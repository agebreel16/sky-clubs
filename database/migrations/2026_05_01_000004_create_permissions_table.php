<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: create_permissions_table
 *
 * Purpose: Defines all granular permissions that can be assigned to roles.
 * Uses kebab-case naming convention for permission identifiers.
 *
 * Permission Groups:
 *  - agents   : View, create, edit, delete agents
 *  - clubs    : View, edit club configurations
 *  - reports  : View and export reports
 *  - users    : Manage system users
 *  - settings : Manage application settings
 *  - audit    : View audit logs
 *  - payments : Process and view payments/rewards
 *
 * Character Set: utf8mb4_unicode_ci
 * Dependencies: None
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            // ─── Primary Key ──────────────────────────────────────────────────────
            $table->uuid('id')->primary()->comment('UUID primary key');

            // ─── Identity ─────────────────────────────────────────────────────────
            $table->string('name', 100)
                ->unique()
                ->comment('Unique kebab-case permission key e.g. "view_agents", "import_data"');

            $table->text('description')
                ->nullable()
                ->comment('Human-readable description of what this permission grants');

            // ─── Grouping ─────────────────────────────────────────────────────────
            $table->string('group', 50)
                ->comment('Category for grouping permissions in UI: agents|clubs|reports|users|settings|audit|payments');

            // ─── Timestamps ───────────────────────────────────────────────────────
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────────
        // Additional Indexes
        // ─────────────────────────────────────────────────────────────────────────
        DB::statement('CREATE INDEX idx_group ON permissions (`group`)');
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
