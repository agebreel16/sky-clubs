<?php

namespace Tests\Feature;

use App\Filament\Resources\AgentResource\Pages\ViewAgent;
use App\Models\Agent;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ViewAgentApiInspectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_loads_successfully_for_authenticated_admin(): void
    {
        $admin = User::factory()->create([
            'role'      => 'super_admin',
            'is_active' => true,
        ]);

        $agent = Agent::create([
            'agent_name'         => 'TEST_AGENT',
            'baseline_count'     => 100,
            'pre_campaign_count' => 100,
            'current_total'      => 100,
            'transfer_count'     => 0,
            'new_line_count'     => 0,
            'current_club_id'    => null,
            'is_first_arrival'   => false,
        ]);

        $response = $this->actingAs($admin)->get("/admin/agents/{$agent->agent_id}");

        $response->assertSuccessful();
        $response->assertSee('API TEST');
    }

    public function test_fetch_raw_api_responses_reports_missing_configuration(): void
    {
        $agent = Agent::create([
            'agent_name'         => 'TEST_AGENT_2',
            'baseline_count'     => 100,
            'pre_campaign_count' => 100,
            'current_total'      => 100,
            'transfer_count'     => 0,
            'new_line_count'     => 0,
            'current_club_id'    => null,
            'is_first_arrival'   => false,
        ]);

        $page   = new ViewAgent();
        $method = new \ReflectionMethod(ViewAgent::class, 'fetchRawApiResponses');
        $result = $method->invoke($page, $agent);

        $this->assertFalse($result['configured']);
        $this->assertEmpty($result['results']);
    }

    public function test_fetch_raw_api_responses_returns_full_raw_json_for_both_apis(): void
    {
        AppSetting::set('deals_api_url', 'https://fake-deals-api.test/endpoint');
        AppSetting::set('deals_api_username', 'test_user');
        AppSetting::set('deals_api_password', 'test_pass');
        AppSetting::set('deals_campaign_start_date', '2026-05-17');

        Http::fake(function ($request) {
            $body = $request->data();

            return match ($body['apiName'] ?? null) {
                'GetSubCustomerDeals' => Http::response(['result' => 'SUCCESS', 'data' => [['task_name' => 'new-order', 'status' => 'Activated', 'count' => 3]]]),
                'GetSubCustomerActiveSubs' => Http::response(['result' => 'SUCCESS', 'data' => [['active_subs' => 42]]]),
                default => Http::response([], 500),
            };
        });

        $agent = Agent::create([
            'agent_name'         => 'TEST_AGENT_3',
            'baseline_count'     => 100,
            'pre_campaign_count' => 100,
            'current_total'      => 100,
            'transfer_count'     => 0,
            'new_line_count'     => 0,
            'current_club_id'    => null,
            'is_first_arrival'   => false,
        ]);

        $page   = new ViewAgent();
        $method = new \ReflectionMethod(ViewAgent::class, 'fetchRawApiResponses');
        $result = $method->invoke($page, $agent);

        $this->assertTrue($result['configured']);
        $this->assertTrue($result['results']['GetSubCustomerDeals']['ok']);
        $this->assertStringContainsString('new-order', $result['results']['GetSubCustomerDeals']['raw']);
        $this->assertTrue($result['results']['GetSubCustomerActiveSubs']['ok']);
        $this->assertStringContainsString('42', $result['results']['GetSubCustomerActiveSubs']['raw']);

        // لا كلمة مرور مطلقاً ضمن أي بيانات مُعادة للعرض
        $this->assertStringNotContainsString('test_pass', json_encode($result));
    }
}
