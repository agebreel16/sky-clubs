<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecomputeAgentNewLineCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_corrects_new_line_count_from_stored_data_without_touching_other_fields(): void
    {
        // بيانات بالصيغة القديمة الخاطئة: new_line_count = current_total - transfer_count (يشمل الرصيد القديم)
        $agent = Agent::create([
            'agent_name'         => 'OLD_FORMULA_AGENT',
            'baseline_count'     => 1660,
            'pre_campaign_count' => 1660,
            'current_total'      => 1670,
            'transfer_count'     => 5,
            'new_line_count'     => 1665, // خاطئ: 1670 - 5 (الصيغة القديمة)
            'current_club_id'    => null,
            'is_first_arrival'   => false,
        ]);

        $this->artisan('app:recompute-new-line-count')->assertSuccessful();

        $fresh = $agent->fresh();

        // الصحيح: campaign_increase = 1670 - 1660 = 10 ; new_line_count = 10 - 5 = 5
        $this->assertEquals(5, $fresh->new_line_count);
        $this->assertEquals(1670, $fresh->current_total);
        $this->assertEquals(5, $fresh->transfer_count);
        $this->assertEquals(1660, $fresh->baseline_count);
        $this->assertEquals(0, AuditLog::where('model_id', $agent->agent_id)->count());
    }

    public function test_does_not_update_already_correct_agents(): void
    {
        $agent = Agent::create([
            'agent_name'         => 'ALREADY_CORRECT_AGENT',
            'baseline_count'     => 100,
            'pre_campaign_count' => 100,
            'current_total'      => 130,
            'transfer_count'     => 20,
            'new_line_count'     => 10, // صحيح بالفعل: (130-100) - 20
            'current_club_id'    => null,
            'is_first_arrival'   => false,
        ]);

        $originalUpdatedAt = $agent->updated_at;

        $this->artisan('app:recompute-new-line-count')->assertSuccessful();

        $this->assertEquals($originalUpdatedAt->timestamp, $agent->fresh()->updated_at->timestamp);
    }
}
