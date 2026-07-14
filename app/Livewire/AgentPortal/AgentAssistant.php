<?php

namespace App\Livewire\AgentPortal;

use App\Models\Agent;
use App\Models\Club;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AgentAssistant extends Component
{
    #[Locked]
    public Agent $agent;

    public string  $userMessage = '';
    public array   $messages    = [];
    public ?string $error       = null;

    public function mount(Agent $agent): void
    {
        $this->agent    = $agent;
        $this->messages = session("ai_chat_{$this->agent->agent_id}", []);
    }

    public function sendMessage(): void
    {
        $text = trim($this->userMessage);
        if (!$text) return;

        $this->error       = null;
        $this->userMessage = '';
        $this->messages[]  = ['role' => 'user', 'content' => $text];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.groq.key'),
                    'Content-Type'  => 'application/json',
                ])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'      => 'llama-3.3-70b-versatile',
                    'max_tokens' => 600,
                    'messages'   => [
                        ['role' => 'system', 'content' => $this->buildSystemPrompt()],
                        ...array_slice($this->messages, -20),
                    ],
                ]);

            if ($response->successful()) {
                $reply = $response->json('choices.0.message.content', '—');
                $this->messages[] = ['role' => 'assistant', 'content' => $reply];
            } else {
                array_pop($this->messages);
                $this->error = 'حدث خطأ في الاتصال بالمساعد. حاول مجدداً.';
            }
        } catch (\Throwable) {
            array_pop($this->messages);
            $this->error = 'تعذّر الوصول للمساعد. تحقق من اتصالك بالإنترنت.';
        }

        session(["ai_chat_{$this->agent->agent_id}" => $this->messages]);
    }

    public function clearChat(): void
    {
        $this->messages = [];
        $this->error    = null;
        session()->forget("ai_chat_{$this->agent->agent_id}");
    }

    private function buildSystemPrompt(): string
    {
        $agent    = $this->agent;
        $club     = $agent->club;
        $increase = $agent->campaign_increase;

        $clubs    = Club::where('is_active', true)->orderBy('club_order')->get();
        $nextClub = $clubs->first(fn($c) => $c->club_order > ($club?->club_order ?? 0));

        $rankText = '—';
        if ($club) {
            $rank     = Agent::where('current_club_id', $agent->current_club_id)
                ->where('transfer_count', '>', $agent->transfer_count)->count() + 1;
            $rankText = "#{$rank} في {$club->club_name}";
        }

        $clubsTable = '';
        foreach ($clubs as $c) {
            $clubsTable .= "  • {$c->club_name}: إجمالي زيادة ≥ {$c->required_increase} + تحويل ≥ {$c->required_transfer_count}\n";
        }

        // المكافآت المالية
        $rewards      = $agent->rewards;
        $totalReward  = number_format((float) $rewards->sum('amount'), 0);
        $paidReward   = number_format((float) $rewards->where('payment_status', 'paid')->sum('amount'), 0);
        $pendingReward = number_format((float) $rewards->where('payment_status', 'pending')->sum('amount'), 0);
        $rewardCount  = $rewards->count();

        // فرص السحب النشطة
        $activeOpps     = $agent->opportunities()->where('is_active', true)->get();
        $totalOpps      = $activeOpps->count();
        $entryOpps      = $activeOpps->where('type', 'entry')->count();
        $maintOpps      = $activeOpps->where('type', 'maintenance')->count();
        $bonusOpps      = $activeOpps->where('type', 'bonus')->count();
        $firstOpps      = $activeOpps->where('type', 'first_arrival')->count();
        $maintThisMonth = $activeOpps->filter(fn($o) =>
            $o->type === 'maintenance' &&
            \Carbon\Carbon::parse($o->earned_date)->isSameMonth(now())
        )->isNotEmpty();

        $p  = "## شو دورك\n";
        $p .= "أنت صديق ووكيل متخصص بحملة Sky Clubs. بتحكي مع الوكلاء بطريقة حلوة وقريبة — مش روبوت رسمي جامد. بتساعدهم وبتشجعهم بناءً على بياناتهم الحقيقية.\n\n";

        $p .= "## أسلوبك في الحديث\n";
        $p .= "- **حكي بالعامية الفلسطينية** — مش فصحى رسمية. استخدم: 'شو'، 'هيك'، 'اشي'، 'وين'، 'كيفك'، 'يلا'، 'معلش'، 'زبدة الموضوع'... إلخ.\n";
        $p .= "- **كن دافئاً ومشجعاً** — زي صديق بيهتم فعلاً. شجّع، امدح على التقدم، ولا تكن بارداً.\n";
        $p .= "- **اختصر** — 3-4 جمل بالغالب تكفي. ما تطوّل ما في داعي.\n";
        $p .= "- **ابدأ الرد بشكل طبيعي** — لا تبدأ دايماً بـ'بالطبع!' أو 'أهلاً!' لكل رسالة. تنوّع.\n";
        $p .= "- **ما تكرر نفسك** — لو قلت معلومة مرة، ما تعيدها إلا لو سأل.\n\n";

        $p .= "## قواعد ثابتة\n";
        $p .= "1. **عربي/فلسطيني دايماً** — حتى لو الوكيل حكى بلغة ثانية.\n";
        $p .= "2. **أرقام من البيانات بس** — لا تخترع أرقام أو نسب من عندك.\n";
        $p .= "3. **لو ما عندك جواب** — قول بصراحة: 'هاد ما وصلتني معلوماته، بتقدر تسأل الإدارة مباشرة.'\n";
        $p .= "4. **بس حملة Sky Clubs** — ما تدخل بمواضيع برّا (سياسة، دين، صحة...).\n\n";

        $p .= "## معلومات الحملة\n";
        $p .= "اسم الحملة: Sky Clubs\n";
        $p .= "مدة الحملة: 2026-05-01 إلى 2027-04-30\n";
        $p .= "الأندية (تصاعدياً) وشروطها:\n{$clubsTable}";
        $p .= "قاعدة الترقية: يجب تحقيق **كلا الشرطين** في آنٍ واحد.\n";
        $p .= "قاعدة الترتيب: يُرتَّب الوكلاء داخل النادي حسب عدد خطوط التحويل.\n\n";

        $p .= "## بيانات الوكيل المتحدث إليه الآن\n";
        $p .= "الاسم: {$agent->agent_name}\n";
        $p .= "النادي الحالي: " . ($club?->club_name ?? 'خارج الأندية — لم يصل أي نادٍ بعد') . "\n";
        $p .= "خطوط التحويل: {$agent->transfer_count}\n";
        $p .= "الخطوط الجديدة: {$agent->new_line_count}\n";
        $p .= "إجمالي الزيادة في الحملة: {$increase}\n";
        $p .= "الترتيب في النادي: {$rankText}\n";

        if ($nextClub) {
            $gapI = max(0, $nextClub->required_increase - $increase);
            $gapT = max(0, $nextClub->required_transfer_count - $agent->transfer_count);

            $p .= "\n## الفجوة للنادي التالي — {$nextClub->club_name}\n";
            $p .= "متبقٍ من الزيادة الإجمالية: {$gapI} خط\n";
            $p .= "متبقٍ من التحويل: {$gapT} خط\n";

            if ($gapI <= 0 && $gapT <= 0) {
                $p .= "⚠ الوكيل مؤهل تقنياً للترقية — لكنها في انتظار مراجعة وموافقة الإدارة.\n";
            }
        } else {
            $p .= "\n## ملاحظة\n";
            $p .= "الوكيل في أعلى نادٍ متاح. حثّه على الحفاظ على مكانته وزيادة فرص السحب (تأتي من الخطوط الجديدة في نادي الذروة).\n";
        }

        // ── المكافآت المالية ──────────────────────────────────────────
        $p .= "\n## المكافآت المالية للوكيل\n";
        if ($rewardCount > 0) {
            $p .= "إجمالي المكافآت المستحقة: {$totalReward} ₪ ({$rewardCount} مكافأة)\n";
            $p .= "المبلغ المدفوع: {$paidReward} ₪\n";
            $p .= "المبلغ المعلّق (لم يُصرف بعد): {$pendingReward} ₪\n";
        } else {
            $p .= "لا توجد مكافآت مالية مسجّلة حتى الآن.\n";
        }

        // ── فرص السحب الحالية ───────────────────────────────────────
        $p .= "\n## فرص السحب النشطة للوكيل\n";
        $p .= "إجمالي الفرص النشطة: {$totalOpps} فرصة\n";
        if ($totalOpps > 0) {
            if ($entryOpps > 0)  $p .= "  • دخول الأندية: {$entryOpps} فرصة\n";
            if ($firstOpps > 0)  $p .= "  • أوائل (حصرية): {$firstOpps} فرصة\n";
            if ($maintOpps > 0)  $p .= "  • محافظة شهرية: {$maintOpps} فرصة\n";
            if ($bonusOpps > 0)  $p .= "  • أداء (تحويلات): {$bonusOpps} فرصة\n";
            $p .= "محافظة هذا الشهر: " . ($maintThisMonth ? "✓ مسجّلة" : "⚠ لم تُسجَّل بعد") . "\n";
        }

        // ── جوائز وقواعد الأندية ────────────────────────────────────
        if ($club) {
            $grandPrize = number_format((float) $club->grand_prize_amount, 0);
            $p .= "\n## جوائز النادي الحالي — {$club->club_name}\n";
            $p .= "جائزة السحب الكبرى: {$grandPrize} ₪\n";
            $p .= "الحد الأدنى للوكلاء لفتح السحب: {$club->seat_capacity} وكيل\n";
            if ($club->has_bonus_opportunities && $club->bonus_per_numbers) {
                $earnedBonus = (int) floor($agent->transfer_count / $club->bonus_per_numbers);
                $p .= "قاعدة فرص الأداء: كل {$club->bonus_per_numbers} تحويل = فرصة سحب إضافية\n";
                $p .= "فرص الأداء التي يستحقها الوكيل بتحويلاته الحالية ({$agent->transfer_count}): {$earnedBonus} فرصة\n";
            }
        }

        if ($nextClub) {
            $baseRew   = number_format((float) $nextClub->base_reward_amount, 0);
            $firstRew  = number_format((float) $nextClub->first_arrival_reward_amount, 0);
            $grandNext = number_format((float) $nextClub->grand_prize_amount, 0);
            $p .= "\n## ماذا يكسب الوكيل لو دخل النادي التالي — {$nextClub->club_name}\n";
            $p .= "مكافأة الدخول: {$baseRew} ₪\n";
            $p .= "مكافأة الأوائل (أول {$nextClub->first_arrival_count} وكيل فقط): {$firstRew} ₪\n";
            $p .= "فرص السحب عند الدخول: {$nextClub->entry_opportunities} فرصة مباشرة\n";
            $p .= "جائزة السحب الكبرى لهذا النادي: {$grandNext} ₪\n";
            if ($nextClub->has_bonus_opportunities && $nextClub->bonus_per_numbers) {
                $p .= "فرصة أداء: كل {$nextClub->bonus_per_numbers} تحويل = فرصة سحب إضافية\n";
            }
        }

        $p .= "\n## كيف تتعامل مع الأسئلة الشائعة\n";
        $p .= "- 'شو ناقصني عشان أترقى؟' → اذكر الفجوتَين الدقيقتَين من البيانات أعلاه.\n";
        $p .= "- 'متى بوصل النادي التالي؟' → لا تحدد وقتاً لأنك لا تعرف وتيرته المستقبلية. قل 'يعتمد على وتيرتك القادمة.'\n";
        $p .= "- 'كيف أحسّن ترتيبي؟' → التحويلات هي معيار الترتيب — ركّز عليها.\n";
        $p .= "- 'ليش ما اترقيت؟' → اشرح أن الترقية تحتاج موافقة الإدارة، وتأكد معه إذا كان استوفى الشرطين.\n";
        $p .= "- 'قديش فرص سحب عندي؟' → اذكر الإجمالي والتفصيل من قسم فرص السحب.\n";
        $p .= "- 'قديش راح أربح لو دخلت النادي التالي؟' → اذكر مكافأة الدخول + فرص السحب من قسم النادي التالي.\n";
        $p .= "- 'لو زدت X تحويل كم فرصة زيادية؟' → احسب بناءً على قاعدة الأداء (bonus_per_numbers) إن كانت متاحة في ناديه.\n";
        $p .= "- 'متى يصير السحب؟' → الموعد تحدده الإدارة عند اكتمال الحد الأدنى من الوكلاء. لا تحدد موعداً.\n";
        $p .= "- سؤال خارج الحملة → 'أنا متخصص بحملة Sky Clubs فقط، لا أستطيع المساعدة في هذا الموضوع.'\n";

        return $p;
    }

    public function render()
    {
        return view('livewire.agent-portal.assistant');
    }
}
