<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_roles_table
 *
 * Purpose: Defines all application roles for Role-Based Access Control (RBAC).
 * Roles are assigned to users and linked to permissions via role_has_permissions.
 *
 * Default Roles (seeded separately):
 *  - super_admin    : Full system access (1-2 people max)
 *  - admin          : Complete administrative access (except user management)
 *  - supervisor     : Team management and reporting
 *  - data_entry     : Can upload files and view basic data
 *  - viewer         : Read-only access to all data
 *  - finance_officer: Payment processing capabilities
 *
 * Character Set: utf8mb4_unicode_ci
 * Dependencies: None
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            // ─── Primary Key ──────────────────────────────────────────────────────
            $table->uuid('id')->primary()->comment('UUID primary key');

            // ─── Identity ─────────────────────────────────────────────────────────
            $table->string('name', 100)
                ->unique()
                ->comment('Unique role identifier e.g. "super_admin", "finance_officer"');

            $table->text('description')
                ->nullable()
                ->comment('Human-readable explanation of this role\'s purpose and access level');

            // ─── Timestamps ───────────────────────────────────────────────────────
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
