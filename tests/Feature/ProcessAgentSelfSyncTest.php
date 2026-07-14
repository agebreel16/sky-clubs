<?php

namespace Tests\Feature;

use App\Jobs\ProcessAgentSelfSync;
use App\Jobs\ProcessDataImport;
use App\Models\Agent;
use App\Models\AppSetting;
use App\Models\Club;
use App\Models\DataImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * يغطي إصلاح اختلاف الأرقام بين المزامنة الجماعية (ProcessDataImport::readDealsApi)
 * والمزامنة الذاتية (ProcessAgentSelfSync): كلاهما يجب أن يستدعي GetSubCustomerDeals
 * وGetSubCustomerActiveSubs الآن، وينتجا نفس current_total/new_line_count/transfer_count
 * لنفس بيانات API.
 */
class ProcessAgentSelfSyncTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;
    private Agent $agent;

    protected function setUp(): void
    {
        parent::setUp();

        AppSetting::set('deals_api_url', 'https://fake-deals-api.test/endpoint');
        AppSetting::set('deals_api_username', 'test_user');
        AppSetting::set('deals_api_password', 'test_pass');
        AppSetting::set('deals_campaign_start_date', '2026-05-17');

        $this->club = Club::create([
            'club_name'                    => 'نادي الانطلاق',
            'club_order'                   => 1,
            'required_increase'            => 25,
            'required_transfer_count'      => 15,
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

        $this->agent = Agent::create([
            'agent_name'         => 'TEST_AGENT',
            'baseline_count'     => 100,
            'pre_campaign_count' => 100,
            'current_total'      => 100,
            'transfer_count'     => 0,
            'new_line_count'     => 0,
            'current_club_id'    => null,
            'is_first_arrival'   => false,
        ]);
    }

    private function fakeDealsApi(int $activeSubs, int $transfers): void
    {
        Http::fake(function ($request) use ($activeSubs, $transfers) {
            $body = $request->data();

            return match ($body['apiName'] ?? null) {
                'GetSubCustomerActiveSubs' => Http::response([
                    'result' => 'SUCCESS',
                    'data'   => [['active_subs' => $activeSubs]],
                ]),
                'GetSubCustomerDeals' => Http::response([
                    'result' => 'SUCCESS',
                    'data'   => [
                        ['task_name' => 'number-portability', 'status' => 'Activated', 'count' => $transfers],
                    ],
                ]),
                default => Http::response(['result' => 'FAILED'], 500),
            };
        });
    }

    public function test_self_sync_uses_active_subs_and_matches_bulk_import_formula(): void
    {
        $this->fakeDealsApi(activeSubs: 130, transfers: 20);

        (new ProcessAgentSelfSync($this->agent))->handle();

        $fresh = $this->agent->fresh();
        $this->assertEquals(130, $fresh->current_total);
        $this->assertEquals(20, $fresh->transfer_count);
        // campaign_increase = current_total(130) - baseline_count(100) = 30 ; new_line_count = 30 - transfers(20)
        $this->assertEquals(10, $fresh->new_line_count);
    }

    public function test_self_sync_updates_last_sync_time_on_deals_api_failure(): void
    {
        Http::fake(function ($request) {
            $body = $request->data();

            return match ($body['apiName'] ?? null) {
                'GetSubCustomerDeals' => Http::response(['result' => 'FAILED']),
                'GetSubCustomerActiveSubs' => Http::response(['result' => 'SUCCESS', 'data' => [['active_subs' => 999]]]),
                default => Http::response([], 500),
            };
        });

        (new ProcessAgentSelfSync($this->agent))->handle();

        $fresh = $this->agent->fresh();
        $this->assertEquals(100, $fresh->current_total, 'لا يجب تغيير الأرقام عند فشل Deals API');
        $this->assertNotNull($fresh->last_self_sync_at);
    }

    public function test_self_sync_leaves_numbers_unchanged_when_active_subs_api_fails(): void
    {
        Http::fake(function ($request) {
            $body = $request->data();

            return match ($body['apiName'] ?? null) {
                'GetSubCustomerDeals' => Http::response(['result' => 'SUCCESS', 'data' => []]),
                'GetSubCustomerActiveSubs' => Http::response(['result' => 'FAILED']),
                default => Http::response([], 500),
            };
        });

        (new ProcessAgentSelfSync($this->agent))->handle();

        $fresh = $this->agent->fresh();
        $this->assertEquals(100, $fresh->current_total, 'لا fallback للصيغة الجمعية القديمة عند فشل ActiveSubs API');
    }

    public function test_violator_agent_never_calls_api(): void
    {
        Http::fake();
        $this->agent->update(['is_violator' => true, 'violator_since' => now(), 'violator_reason' => 'test']);

        (new ProcessAgentSelfSync($this->agent->fresh()))->handle();

        Http::assertNothingSent();
        $this->assertNotNull($this->agent->fresh()->last_self_sync_at);
    }

    /**
     * الاختبار الحاسم: نفس بيانات الـ API الوهمية عبر مسارين مختلفين (self-sync
     * وimport جماعي) يجب أن تنتج نفس current_total/transfer_count/new_line_count
     * تماماً لوكيلين متطابقين.
     */
    public function test_self_sync_and_bulk_import_produce_identical_results(): void
    {
        $this->fakeDealsApi(activeSubs: 145, transfers: 30);

        $agentB = Agent::create([
            'agent_name'         => 'TEST_AGENT_B',
            'baseline_count'     => 100,
            'pre_campaign_count' => 100,
            'current_total'      => 100,
            'transfer_count'     => 0,
            'new_line_count'     => 0,
            'current_club_id'    => null,
            'is_first_arrival'   => false,
        ]);

        // مسار أ: self-sync
        (new ProcessAgentSelfSync($this->agent))->handle();

        // مسار ب: استيراد جماعي (deals_api) لنفس الوكيل الثاني
        $uploader = User::factory()->create();
        $import   = DataImport::create([
            'data_date'   => today(),
            'source_type' => 'deals_api',
            'status'      => 'pending',
            'uploaded_by' => $uploader->id,
        ]);
        (new ProcessDataImport($import))->handle();

        $freshA = $this->agent->fresh();
        $freshB = $agentB->fresh();

        $this->assertEquals($freshA->current_total, $freshB->current_total);
        $this->assertEquals($freshA->transfer_count, $freshB->transfer_count);
        $this->assertEquals($freshA->new_line_count, $freshB->new_line_count);

        $this->assertEquals(145, $freshB->current_total);
        $this->assertEquals(30, $freshB->transfer_count);
        // campaign_increase = 145 - 100 = 45 ; new_line_count = 45 - transfers(30)
        $this->assertEquals(15, $freshB->new_line_count);
    }
}
