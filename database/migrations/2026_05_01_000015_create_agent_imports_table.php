<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_imports', function (Blueprint $table) {
            $table->uuid('import_id')->primary();

            $table->string('original_filename', 500)->nullable();
            $table->string('stored_filepath', 1000)->nullable();
            $table->string('file_hash', 64)->nullable();

            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('rejected_count')->default(0);
            $table->unsignedInteger('errors_count')->default(0);

            $table->enum('status', ['pending', 'processing', 'success', 'failed'])->default('pending');
            $table->text('error_message')->nullable();

            $table->uuid('uploaded_by')->nullable();
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');

            $table->unsignedInteger('processing_duration_ms')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });

        DB::statement('CREATE INDEX idx_agent_imports_status ON agent_imports (status)');
        DB::statement('CREATE INDEX idx_agent_imports_created_at ON agent_imports (created_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_imports');
    }
};
