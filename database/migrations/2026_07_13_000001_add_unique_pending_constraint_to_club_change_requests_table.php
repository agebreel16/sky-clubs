<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // تنظيف بيانات سابقة: أي وكيل عنده أكثر من صف pending حالياً (قبل وجود هذا القيد)،
        // أبقِ الأحدث فقط وألغِ الباقي، لكي يمكن إضافة الفهرس الفريد أدناه بدون فشل.
        DB::statement("
            UPDATE club_change_requests t
            JOIN (
                SELECT agent_id, MAX(created_at) AS latest_created_at
                FROM club_change_requests
                WHERE status = 'pending'
                GROUP BY agent_id
                HAVING COUNT(*) > 1
            ) dup ON t.agent_id = dup.agent_id
            SET t.status = 'auto_cancelled'
            WHERE t.status = 'pending'
              AND t.created_at < dup.latest_created_at
        ");

        // عمود مُولَّد (virtual): يعكس agent_id فقط طالما status = 'pending'، وإلا NULL.
        // MySQL يعامل كل NULL كقيمة مستقلة داخل فهرس UNIQUE، فهذا الفهرس يضمن
        // "طلب pending واحد كحد أقصى لكل وكيل" دون التأثير على الصفوف غير المعلّقة.
        DB::statement("
            ALTER TABLE club_change_requests
            ADD COLUMN pending_agent_id CHAR(36) COLLATE utf8mb4_unicode_ci
                GENERATED ALWAYS AS (CASE WHEN status = 'pending' THEN agent_id ELSE NULL END) VIRTUAL
        ");

        DB::statement('
            ALTER TABLE club_change_requests
            ADD UNIQUE INDEX uniq_ccr_pending_agent (pending_agent_id)
        ');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE club_change_requests DROP INDEX uniq_ccr_pending_agent');
        DB::statement('ALTER TABLE club_change_requests DROP COLUMN pending_agent_id');
    }
};
