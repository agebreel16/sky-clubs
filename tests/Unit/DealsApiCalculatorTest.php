<?php

namespace Tests\Unit;

use App\Support\DealsApiCalculator;
use PHPUnit\Framework\TestCase;

class DealsApiCalculatorTest extends TestCase
{
    public function test_is_success_detects_success_result(): void
    {
        $this->assertTrue(DealsApiCalculator::isSuccess(['result' => 'SUCCESS']));
        $this->assertFalse(DealsApiCalculator::isSuccess(['result' => 'FAILED']));
        $this->assertFalse(DealsApiCalculator::isSuccess(null));
        $this->assertFalse(DealsApiCalculator::isSuccess([]));
    }

    public function test_extract_transfer_count_sums_activated_portability_rows(): void
    {
        $body = [
            'data' => [
                ['task_name' => 'number-portability', 'status' => 'Activated', 'count' => 10],
                ['task_name' => 'number-portability', 'status' => 'Activated', 'count' => 5],
                ['task_name' => 'number-portability', 'status' => 'Pending', 'count' => 100], // يُتجاهل
                ['task_name' => 'new-order', 'status' => 'Activated', 'count' => 999], // يُتجاهل
            ],
        ];

        $this->assertEquals(15, DealsApiCalculator::extractTransferCount($body));
    }

    public function test_extract_transfer_count_with_missing_data_key(): void
    {
        $this->assertEquals(0, DealsApiCalculator::extractTransferCount([]));
    }

    public function test_extract_active_subs_reads_first_row(): void
    {
        $body = ['data' => [['active_subs' => 2944], ['active_subs' => 9999]]];

        $this->assertEquals(2944, DealsApiCalculator::extractActiveSubs($body));
    }

    public function test_extract_active_subs_with_empty_data(): void
    {
        $this->assertEquals(0, DealsApiCalculator::extractActiveSubs(['data' => []]));
        $this->assertEquals(0, DealsApiCalculator::extractActiveSubs([]));
    }

    public function test_compute_totals_normal_case(): void
    {
        $result = DealsApiCalculator::computeTotals(activeSubs: 130, transfers: 20, preCampaignCount: 100, baselineCount: 100);

        // campaign_increase = 130 - 100 = 30 ; new_line_count = 30 - 20 = 10
        $this->assertEquals([
            'current_total'  => 130,
            'new_line_count' => 10,
            'transfer_count' => 20,
        ], $result);
    }

    public function test_compute_totals_clips_new_line_count_at_zero_when_transfers_exceed_campaign_increase(): void
    {
        $result = DealsApiCalculator::computeTotals(activeSubs: 50, transfers: 80, preCampaignCount: 40, baselineCount: 40);

        $this->assertEquals(0, $result['new_line_count']);
        $this->assertEquals(50, $result['current_total']);
    }

    public function test_compute_totals_floors_current_total_at_pre_campaign_count(): void
    {
        // مثال حقيقي موثّق في sky.md (TD-012): active_subs أقل من pre_campaign_count
        $result = DealsApiCalculator::computeTotals(activeSubs: 2944, transfers: 0, preCampaignCount: 3106, baselineCount: 3106);

        $this->assertEquals(3106, $result['current_total']);
    }

    public function test_compute_totals_new_line_count_plus_transfers_equals_campaign_increase(): void
    {
        // الثابت الذي أبلغ عنه المستخدم كخلل: new_line_count + transfer_count يجب أن يساوي campaign_increase
        $result = DealsApiCalculator::computeTotals(activeSubs: 1670, transfers: 5, preCampaignCount: 1660, baselineCount: 1660);

        $campaignIncrease = max(0, $result['current_total'] - 1660);

        $this->assertEquals($campaignIncrease, $result['new_line_count'] + $result['transfer_count']);
        $this->assertEquals(5, $result['new_line_count']);
    }
}
