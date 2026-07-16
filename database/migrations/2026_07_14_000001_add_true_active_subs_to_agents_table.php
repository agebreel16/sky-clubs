<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * يضيف true_active_subs: الرقم الحي الحقيقي من Deals API (GetSubCustomerActiveSubs)
 * بدون أي "Floor" عند pre_campaign_count — بعكس current_total المحمي دائماً من النزول
 * تحت pre_campaign_count. يبقى null لغاية أول مزامنة ناجحة تحسبه.
 *
 * الفرق (baseline_count - true_active_subs) هو "تراجع الوكيل الحقيقي" عن بداية الحملة،
 * ويُسمح له أن يكون سالباً (يعني لا تراجع) أو موجباً (يعني خسارة فعلية).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->unsignedInteger('true_active_subs')->nullable()->after('current_total');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn('true_active_subs');
        });
    }
};
