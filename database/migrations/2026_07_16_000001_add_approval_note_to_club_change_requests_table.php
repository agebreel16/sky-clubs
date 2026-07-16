<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_change_requests', function (Blueprint $table) {
            $table->text('approval_note')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('club_change_requests', function (Blueprint $table) {
            $table->dropColumn('approval_note');
        });
    }
};
