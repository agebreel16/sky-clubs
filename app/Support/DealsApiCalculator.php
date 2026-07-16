<?php

namespace App\Support;

/**
 * صيغة موحّدة لقراءة/حساب أرقام الوكيل من استجابات Deals API الخارجي.
 * تُستخدم من ProcessDataImport::readDealsApi() وProcessAgentSelfSync لضمان
 * أن كلا المسارين ينتجان نفس current_total/new_line_count لنفس الوكيل.
 */
final class DealsApiCalculator
{
    public const TASK_TRANSFER = 'number-portability';
    public const TASK_NEW_LINE = 'new-order';
    public const STATUS_ACTIVATED = 'Activated';

    private function __construct()
    {
    }

    public static function isSuccess(?array $body): bool
    {
        return ($body['result'] ?? '') === 'SUCCESS';
    }

    public static function extractTransferCount(array $dealsBody): int
    {
        $rows = collect($dealsBody['data'] ?? []);

        return (int) $rows
            ->where('task_name', self::TASK_TRANSFER)
            ->where('status', self::STATUS_ACTIVATED)
            ->sum(fn ($r) => (int) $r['count']);
    }

    public static function extractActiveSubs(array $subsBody): int
    {
        $row = collect($subsBody['data'] ?? [])->first();

        return $row !== null ? (int) ($row['active_subs'] ?? 0) : 0;
    }

    /**
     * new_line_count يُشتَق من campaign_increase (current_total - baseline_count)
     * وليس من current_total مباشرة، بحيث new_line_count + transfer_count = campaign_increase
     * دائماً (طالما transfer_count <= campaign_increase) — مطابق لصيغة
     * Agent::getCampaignIncreaseAttribute() تماماً (مصدر حقيقة واحد).
     *
     * current_total محمي دائماً بـ Floor عند pre_campaign_count (TD-012) فلا يعكس
     * تراجعاً حقيقياً لو خسر الوكيل خطوطاً قديمة. true_active_subs هو نفس $activeSubs
     * بدون أي Floor — يُخزَّن بجانب current_total فقط للمراقبة (تراجع عن الأساس)،
     * ولا يُستخدم إطلاقاً بمنطق الترقية/التهبيط.
     *
     * @return array{current_total: int, new_line_count: int, transfer_count: int, true_active_subs: int}
     */
    public static function computeTotals(int $activeSubs, int $transfers, int $preCampaignCount, int $baselineCount): array
    {
        $currentTotal     = max($preCampaignCount, $activeSubs);
        $campaignIncrease = max(0, $currentTotal - $baselineCount);

        return [
            'current_total'    => $currentTotal,
            'new_line_count'   => max(0, $campaignIncrease - $transfers),
            'transfer_count'   => $transfers,
            'true_active_subs' => $activeSubs,
        ];
    }

    /**
     * @return array{username: ?string, password: ?string, apiName: string, wildcards: array}
     */
    public static function buildPayload(?string $username, ?string $password, string $apiName, string $agentId, string $from, string $to): array
    {
        return [
            'username'  => $username,
            'password'  => $password,
            'apiName'   => $apiName,
            'wildcards' => [$agentId, $from, $to],
        ];
    }
}
