<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Club;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * يغطي تعديل قسم "الأرقام والإحصائيات" في صفحة عرض الوكيل: التسميات الجديدة،
 * حذف "الخطوط القديمة المفقودة"، وحذف بطاقة "المطلوب للنادي القادم".
 */
class ViewAgentStatsSectionTest extends TestCase
{
    use RefreshDatabase;

    private function makeClub(string $name, int $order, int $requiredIncrease, int $requiredTransfer): Club
    {
        return Club::create([
            'club_name'                    => $name,
            'club_order'                   => $order,
            'required_increase'            => $requiredIncrease,
            'required_transfer_count'      => $requiredTransfer,
            'required_transfer_percentage' => 0.60,
            'base_reward_amount'           => 300,
            'first_arrival_reward_amount'  => 600,
            'first_arrival_count'          => 10,
            'seat_capacity'                => 90,
            'grand_prize_amount'           => 15000,
            'entry_opportunities'          => 1,
            'demotion_timer_days'          => 7,
            'has_bonus_opportunities'      => false,
            'is_active'                    => true,
        ]);
    }

    public function test_page_shows_new_labels_and_hides_deleted_field(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $launchClub = $this->makeClub('نادي الانطلاق', 1, 25, 15);
        $this->makeClub('نادي التفوق', 2, 50, 30);

        $agent = Agent::create([
            'agent_name'         => 'TEST_AGENT',
            'baseline_count'     => 100,
            'pre_campaign_count' => 100,
            'current_total'      => 130,
            'transfer_count'     => 20,
            'new_line_count'     => 10,
            'current_club_id'    => $launchClub->club_id,
            'is_first_arrival'   => false,
        ]);

        $response = $this->actingAs($admin)->get("/admin/agents/{$agent->agent_id}");

        $response->assertSuccessful();
        $response->assertSee('الخطوط قبل الحملة');
        $response->assertSee('الإجمالي حتى الآن');
        $response->assertSee('الخطوط داخل الحملة');
        $response->assertSee('خطوط التحويل');
        $response->assertSee('الخطوط الجديدة');
        $response->assertDontSee('من أصل');
        $response->assertDontSee('الخطوط القديمة المفقودة');
        $response->assertDontSee('الأساس المجمّد');
        $response->assertDontSee('نسبة التحويل');
        $response->assertDontSee('المطلوب للنادي القادم');
    }
}
