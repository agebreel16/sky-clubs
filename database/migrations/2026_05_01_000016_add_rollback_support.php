<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Track which import created each agent (for rollback)
        Schema::table('agents', function (Blueprint $table) {
            $table->uuid('agent_import_id')->nullable()->after('notes');
            $table->foreign('agent_import_id')
                ->references('import_id')
                ->on('agent_imports')
                ->onDelete('set null');
        });

        // Add rolled_back_at timestamp + expand status enum on agent_imports
        Schema::table('agent_imports', function (Blueprint $table) {
            $table->timestamp('rolled_back_at')->nullable()->after('processing_duration_ms');
        });

        DB::statement("ALTER TABLE agent_imports MODIFY COLUMN status ENUM('pending','processing','success','failed','rolled_back') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropForeign(['agent_import_id']);
            $table->dropColumn('agent_import_id');
        });

        DB::statement("ALTER TABLE agent_imports MODIFY COLUMN status ENUM('pending','processing','success','failed') NOT NULL DEFAULT 'pending'");

        Schema::table('agent_imports', function (Blueprint $table) {
            $table->dropColumn('rolled_back_at');
        });
    }
};
