<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: create_role_has_permissions_table
 *
 * Purpose: RBAC junction table mapping roles to their assigned permissions.
 * This is a many-to-many relationship table (roles ↔ permissions).
 *
 * Compound Primary Key: (role_id, permission_id)
 * Both columns are part of PK, ensuring no duplicate assignments.
 *
 * Character Set: utf8mb4_unicode_ci
 * Dependencies: roles, permissions
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_has_permissions', function (Blueprint $table) {
            // ─── Compound Primary Key (role_id + permission_id) ───────────────────
            $table->uuid('role_id')
                ->comment('FK → roles.id. CASCADE on delete/update.');

            $table->uuid('permission_id')
                ->comment('FK → permissions.id. CASCADE on delete/update.');

            // Compound primary key — prevents duplicate assignments
            $table->primary(['role_id', 'permission_id']);

            // ─── Foreign Keys ─────────────────────────────────────────────────────
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });

        // ─────────────────────────────────────────────────────────────────────────
        // Additional Index: look up all roles that have a specific permission
        // ─────────────────────────────────────────────────────────────────────────
        DB::statement('CREATE INDEX idx_rhp_permission_id ON role_has_permissions (permission_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('role_has_permissions');
    }
};
