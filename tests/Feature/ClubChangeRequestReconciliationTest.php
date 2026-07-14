<?php

namespace Tests\Feature;

use App\Filament\Resources\AgentResource\Pages\CreateAgent;
use App\Filament\Resources\ClubChangeRequestResource;
use App\Models\Agent;
use App\Models\Club;
use App\Models\ClubChangeRequest;
use App\Models\Reward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * يغطي 3 ثغرات اكتُشفت بتدقيق منطق الترقية/التهبيط:
 *
 * 1. تعديل يدوي عبر AgentObserver::checkAndApplyPromotion() لم يكن يُلغي أي
 *    ClubChangeRequest معلّق قديم لنفس الوكيل → خطر مكافآت مضاعفة.
 * 2. CreateAgent كان يُسند current_club_id مباشرة بدون موافقة ولا مكافأة.
 * 3. سباق تزامن بين ProcessDataImport وProcessAgentSelfSync قد يُنشئ طلبين
 *    pending لنفس الوكيل — الآن يُمنع بقيد UNIQUE على قاعدة البيانات.
 */
class ClubChangeRequestReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;
    private Agent $agent;

    protected function setUp(): void
    {
        parent::setUp();

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

    /**
     * الإصلاح 1أ: تعديل يدوي يُرقّي الوكيل مباشرة عبر AgentObserver يجب أن
     * يُلغي (auto_cancelled) أي طلب pending قديم لنفس الوكيل، ولا ينشئ سوى
     * مكافأة واحدة فقط.
     */
    public function test_direct_promotion_cancels_stale_pending_request(): void
    {
        $stalePending = ClubChangeRequest::create([
            'agent_id'             => $this->agent->agent_id,
            'from_club_id'         => null,
            'to_club_id'           => $this->club->club_id,
            'change_type'          => 'promotion',
            'agent_stats_snapshot' => ['campaign_increase' => 25],
            'status'               => 'pending',
        ]);

        // تعديل يدوي حقيقي عبر Eloquent يُطلق AgentObserver الفعلي
        $this->agent->update([
            'current_total'  => 130, // زيادة = 30 >= required=25
            'transfer_count' => 20,  // >= required_transfer_count=15
        ]);

        $this->assertEquals(
            'auto_cancelled',
            $stalePending->fresh()->status,
            'الطلب المعلّق القديم يجب أن يُلغى تلقائياً بعد الترقية المباشرة'
        );

        $this->assertEquals(
            1,
            Reward::where('agent_id', $this->agent->agent_id)->count(),
            'يجب مكافأة واحدة فقط رغم وجود طلب معلّق قديم لنفس الترقية'
        );

        $this->assertEquals($this->club->club_id, $this->agent->fresh()->current_club_id);
    }

    /**
     * الإصلاح 1ب: إذا وافق الأدمن على طلب pending لكن الوكيل أصبح بالفعل في
     * النادي المستهدف (طُبِّق التغيير عبر مسار آخر)، approveRequest() يجب أن
     * يمتنع عن إنشاء Reward مكرر، ويُلغي الطلب بدلاً من اعتماده.
     */
    public function test_approve_request_is_idempotent_when_already_applied(): void
    {
        // الوكيل بالفعل داخل النادي (محاكاة تطبيق سابق عبر مسار آخر) — Query Builder
        // خام لتجنب إطلاق AgentObserver::handleClubChange() الذي كان سيُنشئ Reward
        // خاصاً به هنا، فيُلوّث الاختبار بمكافأة لا علاقة لها بالسلوك المُختبَر.
        Agent::where('agent_id', $this->agent->agent_id)->update(['current_club_id' => $this->club->club_id]);
        $this->agent->refresh();

        $pending = ClubChangeRequest::create([
            'agent_id'             => $this->agent->agent_id,
            'from_club_id'         => null,
            'to_club_id'           => $this->club->club_id,
            'change_type'          => 'promotion',
            'agent_stats_snapshot' => ['campaign_increase' => 25],
            'status'               => 'pending',
        ]);

        $method = new \ReflectionMethod(ClubChangeRequestResource::class, 'approveRequest');
        $method->invoke(null, $pending, true);

        $this->assertEquals('auto_cancelled', $pending->fresh()->status);
        $this->assertEquals(
            0,
            Reward::where('agent_id', $this->agent->agent_id)->count(),
            'لا يجب إنشاء أي مكافأة عند اعتماد طلب مُطبَّق مسبقاً'
        );
    }

    /**
     * الإصلاح 2: إنشاء وكيل جديد لا يُسند نادياً أبداً حتى لو كانت الأرقام
     * المُدخلة تؤهّله فوراً — يبدأ دائماً خارج الأندية.
     */
    public function test_create_agent_never_auto_assigns_club(): void
    {
        $page = new CreateAgent();

        $method = new \ReflectionMethod(CreateAgent::class, 'mutateFormDataBeforeCreate');
        $result = $method->invoke($page, [
            'agent_name'         => 'NEW_AGENT',
            'baseline_count'     => 100,
            'pre_campaign_count' => 100,
            'current_total'      => 130, // يؤهّل فوراً لو طُبِّق الحساب القديم
            'transfer_count'     => 20,
            'new_line_count'     => 5,
        ]);

        $this->assertNull($result['current_club_id'], 'الوكيل الجديد يجب أن يبدأ خارج الأندية دائماً');
    }

    /**
     * الإصلاح 3: قيد UNIQUE على قاعدة البيانات يمنع وجود أكثر من صف pending
     * واحد لنفس الوكيل — يحاكي سيناريو التزامن بين ProcessDataImport
     * وProcessAgentSelfSync.
     */
    public function test_unique_index_prevents_second_pending_request_for_same_agent(): void
    {
        ClubChangeRequest::create([
            'agent_id'             => $this->agent->agent_id,
            'from_club_id'         => null,
            'to_club_id'           => $this->club->club_id,
            'change_type'          => 'promotion',
            'agent_stats_snapshot' => [],
            'status'               => 'pending',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        ClubChangeRequest::create([
            'agent_id'             => $this->agent->agent_id,
            'from_club_id'         => null,
            'to_club_id'           => $this->club->club_id,
            'change_type'          => 'promotion',
            'agent_stats_snapshot' => [],
            'status'               => 'pending',
        ]);
    }

    /**
     * الإصلاح 3: syncPendingRequest() يتعافى من تصادم القيد الفريد (يحاكي
     * process آخر ربح السباق بإدراج صف pending بين الـ SELECT والـ INSERT)
     * بدلاً من رمي استثناء — يُحدّث الصف الموجود بدلاً من ذلك.
     */
    public function test_sync_pending_request_recovers_from_unique_violation(): void
    {
        // محاكاة: صف pending أُدرج للتو من process آخر (تجاوزاً لمنطق syncPendingRequest نفسه)
        \Illuminate\Support\Facades\DB::table('club_change_requests')->insert([
            'id'                   => (string) \Illuminate\Support\Str::uuid(),
            'agent_id'             => $this->agent->agent_id,
            'from_club_id'         => null,
            'to_club_id'           => $this->club->club_id,
            'change_type'          => 'promotion',
            'agent_stats_snapshot' => json_encode(['campaign_increase' => 10]),
            'status'               => 'pending',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        $result = ClubChangeRequest::syncPendingRequest(
            $this->agent,
            'promotion',
            null,
            $this->club->club_id,
            ['campaign_increase' => 30],
        );

        $this->assertNotNull($result);
        $this->assertEquals(1, ClubChangeRequest::where('agent_id', $this->agent->agent_id)->count());
        $this->assertEquals(30, $result->fresh()->agent_stats_snapshot['campaign_increase']);
    }
}
