<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: create_users_table
 *
 * ⚠️  SECURITY-CRITICAL TABLE ⚠️
 *
 * Purpose: System users — Admin staff and data entry personnel managing the campaign.
 * This table is referenced by data_imports and audit_logs, so it must be created early.
 *
 * Security Features:
 *  - bcrypt hashed passwords (never plaintext)
 *  - Encrypted two_factor_secret via Laravel Crypt facade
 *  - Account lockout after 5 failed attempts (15 minutes)
 *  - TOTP-based 2FA support (Google Authenticator)
 *  - Email verification support
 *  - Soft deletes (GDPR compliance)
 *
 * Character Set: utf8mb4_unicode_ci
 * Dependencies: None (created before FK-dependent tables)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // ─── Primary Key ──────────────────────────────────────────────────────
            $table->uuid('id')->primary()->comment('UUID primary key');

            // ─── Identity ─────────────────────────────────────────────────────────
            $table->string('name', 255)
                ->comment('Full name of the staff member (Arabic or English)');

            $table->string('email', 255)
                ->unique()
                ->comment('Company email — primary login identifier. Must be unique and valid.');

            // ─── Authentication ───────────────────────────────────────────────────
            $table->string('password', 255)
                ->comment('bcrypt-hashed password via Hash::make(). NEVER store plaintext.');

            $table->string('employee_id', 50)
                ->unique()
                ->nullable()
                ->comment('Internal HR employee ID. NULL allowed; UNIQUE when set.');

            // ─── Organizational Role ──────────────────────────────────────────────
            $table->enum('department', ['admin', 'supervisor', 'data_entry', 'finance', 'support'])
                ->default('data_entry')
                ->comment('Organizational department for grouping');

            $table->enum('role', ['super_admin', 'admin', 'supervisor', 'data_entry', 'viewer', 'finance_officer'])
                ->comment('Application RBAC role: determines permissions. super_admin = max 2 users.');

            $table->string('position', 100)
                ->nullable()
                ->comment('Job title e.g. "Campaign Manager", "Data Entry Specialist"');

            $table->string('phone', 20)
                ->nullable()
                ->comment('Contact phone number (optional)');

            // ─── Account Status ───────────────────────────────────────────────────
            $table->boolean('is_active')
                ->default(true)
                ->comment('Enable/disable account without deletion. FALSE = login blocked.');

            $table->boolean('requires_password_change')
                ->default(false)
                ->comment('Force password change on next login (e.g. after admin reset).');

            $table->timestamp('email_verified_at')
                ->nullable()
                ->comment('Timestamp of email verification. NULL = unverified, cannot access system.');

            // ─── Login & Session Tracking ─────────────────────────────────────────
            $table->timestamp('last_login_at')
                ->nullable()
                ->comment('Timestamp of last successful login (UTC). Used for security audit.');

            $table->string('last_login_ip', 45)
                ->nullable()
                ->comment('IPv4 or IPv6 of last successful login. 45 chars supports full IPv6.');

            $table->unsignedInteger('login_attempts')
                ->default(0)
                ->comment('Failed consecutive login attempts. Reset on success. Lock at 5.');

            $table->timestamp('locked_until')
                ->nullable()
                ->comment('Account locked until this UTC time after 5 failed attempts (15-min lockout).');

            // ─── Two-Factor Authentication (TOTP) ────────────────────────────────
            $table->boolean('two_factor_enabled')
                ->default(false)
                ->comment('Whether TOTP-based 2FA is active for this account.');

            $table->string('two_factor_secret', 255)
                ->nullable()
                ->comment('ENCRYPTED Base32 TOTP secret. Use Crypt::encryptString() — NEVER store plaintext.');

            // ─── Session Tokens ───────────────────────────────────────────────────
            $table->string('remember_token', 100)
                ->nullable()
                ->comment('"Remember Me" session token. Invalidated on password change.');

            // ─── Soft Delete ──────────────────────────────────────────────────────
            $table->softDeletes()
                ->comment('Soft delete timestamp. NULL = active. GDPR right-to-be-forgotten support.');

            // ─── Timestamps ───────────────────────────────────────────────────────
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────────
        // CHECK Constraints (MySQL 8.0+)
        // ─────────────────────────────────────────────────────────────────────────
        DB::statement('ALTER TABLE users ADD CONSTRAINT chk_login_attempts_non_negative CHECK (login_attempts >= 0)');

        // ─────────────────────────────────────────────────────────────────────────
        // Additional Indexes
        // ─────────────────────────────────────────────────────────────────────────
        DB::statement('CREATE INDEX idx_department ON users (department)');
        DB::statement('CREATE INDEX idx_role ON users (role)');
        DB::statement('CREATE INDEX idx_is_active ON users (is_active)');
        DB::statement('CREATE INDEX idx_last_login_at ON users (last_login_at)');
        DB::statement('CREATE INDEX idx_locked_until ON users (locked_until)');
        // Composite: active users sorted by last login (dashboard listing)
        DB::statement('CREATE INDEX idx_active_login ON users (is_active, last_login_at DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
