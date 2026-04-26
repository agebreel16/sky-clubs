<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: create_data_imports_table
 *
 * Purpose: Tracks every Excel/API data import with detailed processing statistics.
 * Provides a full audit trail of when data was loaded and what changes resulted.
 *
 * Import Workflow: pending → processing → success | failed
 *
 * Key Design Decision:
 *  - data_date is UNIQUE: only one import per calendar day is accepted.
 *  - file_hash prevents duplicate file uploads (SHA-256 comparison).
 *  - processing_duration_ms enables performance monitoring.
 *
 * Character Set: utf8mb4_unicode_ci
 * Dependencies: users (uploaded_by, processed_by)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_imports', function (Blueprint $table) {
            // ─── Primary Key ──────────────────────────────────────────────────────
            $table->uuid('import_id')->primary()->comment('UUID primary key');

            // ─── Data Identification ──────────────────────────────────────────────
            $table->date('data_date')
                ->unique()
                ->comment('The campaign date this data represents (YYYY-MM-DD). UNIQUE: one import per day.');

            $table->enum('source_type', ['excel', 'api'])
                ->default('excel')
                ->comment('Data origin: excel=uploaded file | api=external API push');

            // ─── File Information (Excel uploads) ─────────────────────────────────
            $table->string('original_filename', 500)
                ->nullable()
                ->comment('Original uploaded filename e.g. "agents_data_2026_05_25.xlsx". NULL for API imports.');

            $table->string('stored_filepath', 1000)
                ->nullable()
                ->comment('Server path of stored file for audit access. NULL for API imports.');

            $table->string('file_hash', 64)
                ->nullable()
                ->comment('SHA-256 hash of uploaded file. Used to detect duplicate submissions. NULL for API.');

            // ─── Processing Statistics ────────────────────────────────────────────
            $table->unsignedInteger('total_agents')
                ->default(0)
                ->comment('Total agent records found in the import file');

            $table->unsignedInteger('processed')
                ->default(0)
                ->comment('Agents successfully validated and processed');

            $table->unsignedInteger('rejected')
                ->default(0)
                ->comment('Agents rejected due to validation errors');

            $table->unsignedInteger('promotions_count')
                ->default(0)
                ->comment('Number of agent promotions triggered by this import');

            $table->unsignedInteger('demotions_count')
                ->default(0)
                ->comment('Number of agent demotions or warnings triggered by this import');

            $table->unsignedInteger('warnings_count')
                ->default(0)
                ->comment('Number of new demotion countdown timers started by this import');

            $table->unsignedInteger('errors_count')
                ->default(0)
                ->comment('Total validation errors encountered during processing');

            // ─── Processing Status ────────────────────────────────────────────────
            $table->enum('status', ['pending', 'processing', 'success', 'failed'])
                ->default('pending')
                ->comment('pending=queued|processing=running|success=done|failed=error');

            $table->text('error_message')
                ->nullable()
                ->comment('Detailed error description if status = "failed". NULL on success.');

            // ─── Operator References ──────────────────────────────────────────────
            $table->uuid('uploaded_by')
                ->nullable()
                ->comment('FK → users.id. Staff member who uploaded the file. NULL for API imports.');

            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->uuid('processed_by')
                ->nullable()
                ->comment('FK → users.id. Staff member who approved/processed the data. NULL if auto-processed.');

            $table->foreign('processed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null')
                ->onUpdate('cascade');

            // ─── Performance Tracking ─────────────────────────────────────────────
            $table->unsignedInteger('processing_duration_ms')
                ->nullable()
                ->comment('Total processing time in milliseconds. Used for performance monitoring and SLA tracking.');

            // ─── Soft Delete ──────────────────────────────────────────────────────
            $table->softDeletes()
                ->comment('Soft delete. Hard delete not permitted — every import must be recoverable.');

            // ─── Timestamps ───────────────────────────────────────────────────────
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────────
        // CHECK Constraints (MySQL 8.0+)
        // ─────────────────────────────────────────────────────────────────────────
        DB::statement('ALTER TABLE data_imports ADD CONSTRAINT chk_total_agents_non_negative CHECK (total_agents >= 0)');
        DB::statement('ALTER TABLE data_imports ADD CONSTRAINT chk_processed_non_negative CHECK (processed >= 0)');
        DB::statement('ALTER TABLE data_imports ADD CONSTRAINT chk_rejected_non_negative CHECK (rejected >= 0)');
        DB::statement('ALTER TABLE data_imports ADD CONSTRAINT chk_promotions_count_non_negative CHECK (promotions_count >= 0)');
        DB::statement('ALTER TABLE data_imports ADD CONSTRAINT chk_demotions_count_non_negative CHECK (demotions_count >= 0)');
        DB::statement('ALTER TABLE data_imports ADD CONSTRAINT chk_warnings_count_non_negative CHECK (warnings_count >= 0)');
        DB::statement('ALTER TABLE data_imports ADD CONSTRAINT chk_errors_count_non_negative CHECK (errors_count >= 0)');
        // Integrity: processed + rejected cannot exceed total
        DB::statement('ALTER TABLE data_imports ADD CONSTRAINT chk_processed_rejected_lte_total CHECK ((processed + rejected) <= total_agents)');
        DB::statement('ALTER TABLE data_imports ADD CONSTRAINT chk_processing_duration_non_negative CHECK (processing_duration_ms IS NULL OR processing_duration_ms >= 0)');

        // ─────────────────────────────────────────────────────────────────────────
        // Additional Indexes
        // ─────────────────────────────────────────────────────────────────────────
        DB::statement('CREATE INDEX idx_import_status ON data_imports (status)');
        DB::statement('CREATE INDEX idx_import_source_type ON data_imports (source_type)');
        DB::statement('CREATE INDEX idx_import_created_at ON data_imports (created_at)');
        DB::statement('CREATE INDEX idx_import_uploaded_by ON data_imports (uploaded_by)');
        DB::statement('CREATE INDEX idx_import_processed_by ON data_imports (processed_by)');
    }

    public function down(): void
    {
        Schema::dropIfExists('data_imports');
    }
};
