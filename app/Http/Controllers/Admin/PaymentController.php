<?php

namespace App\Http\Controllers\Admin;

use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Notification;
use App\Models\Promotion;
use App\Models\Question;
use App\Models\QuestionFolder;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VipPayment;
use App\Models\VipPlan;
use App\Models\VipSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends AdminBaseController
{
    public function vip(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $subscriptions = VipSubscription::with('user')
            ->when($request->filled('sub_q'), function ($query) use ($request) {
                $search = trim((string) $request->query('sub_q'));
                $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->filled('sub_plan'), fn ($query) => $query->where('plan', $request->query('sub_plan')))
            ->when($request->filled('sub_status'), fn ($query) => $query->where('status', $request->query('sub_status')))
            ->when($request->filled('sub_role'), fn ($query) => $query->whereHas('user', fn ($q) => $q->where('role', $request->query('sub_role'))))
            ->when($request->query('sub_scope') === 'expiring', fn ($query) => $query->where('status', 'active')->whereNotNull('expires_at')->whereBetween('expires_at', [now(), now()->addDays(7)]))
            ->when($request->query('sub_scope') === 'overdue', fn ($query) => $query->where('status', 'active')->whereNotNull('expires_at')->where('expires_at', '<', now()))
            ->when($request->query('sub_sort') === 'expires', fn ($query) => $query->orderByRaw('expires_at is null')->orderBy('expires_at'))
            ->when($request->query('sub_sort') === 'user', fn ($query) => $query->orderBy(User::select('name')->whereColumn('users.id', 'vip_subscriptions.user_id')))
            ->when($request->query('sub_sort') === 'oldest', fn ($query) => $query->oldest())
            ->when(! in_array($request->query('sub_sort'), ['expires', 'user', 'oldest'], true), fn ($query) => $query->latest())
            ->paginate(12, ['*'], 'subscriptions_page')
            ->withQueryString();

        $payments = VipPayment::with(['user', 'subscription'])
            ->when($request->filled('pay_q'), function ($query) use ($request) {
                $search = trim((string) $request->query('pay_q'));
                $query->where(fn ($q) => $q->where('txn_ref', 'like', "%{$search}%")
                    ->orWhere('vnp_transaction_no', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")));
            })
            ->when($request->filled('pay_plan'), fn ($query) => $query->where('plan', $request->query('pay_plan')))
            ->when($request->filled('pay_status'), fn ($query) => $query->where('status', $request->query('pay_status')))
            ->when($request->filled('pay_role'), fn ($query) => $query->whereHas('user', fn ($q) => $q->where('role', $request->query('pay_role'))))
            ->when($request->query('pay_scope') === 'needs_reconcile', fn ($query) => $query->where('status', 'paid')->whereNull('vip_subscription_id'))
            ->when($request->query('pay_scope') === 'recent_paid', fn ($query) => $query->where('status', 'paid')->where('paid_at', '>=', now()->subDays(7)))
            ->when($request->query('pay_sort') === 'amount', fn ($query) => $query->orderByDesc('amount'))
            ->when($request->query('pay_sort') === 'paid_at', fn ($query) => $query->orderByDesc('paid_at'))
            ->when($request->query('pay_sort') === 'oldest', fn ($query) => $query->oldest())
            ->when(! in_array($request->query('pay_sort'), ['amount', 'paid_at', 'oldest'], true), fn ($query) => $query->latest())
            ->paginate(12, ['*'], 'payments_page')
            ->withQueryString();

        $summary = [
            'active' => VipSubscription::where('status', 'active')->count(),
            'expiring' => VipSubscription::where('status', 'active')->whereNotNull('expires_at')->whereBetween('expires_at', [now(), now()->addDays(7)])->count(),
            'overdue' => VipSubscription::where('status', 'active')->whereNotNull('expires_at')->where('expires_at', '<', now())->count(),
            'pending' => VipPayment::where('status', 'pending')->count(),
            'paid' => VipPayment::where('status', 'paid')->count(),
            'failed' => VipPayment::whereIn('status', ['failed', 'cancelled'])->count(),
            'revenue_30d' => VipPayment::where('status', 'paid')->where('paid_at', '>=', now()->subDays(30))->sum('amount'),
            'revenue_total' => VipPayment::where('status', 'paid')->sum('amount'),
        ];
        $eligibleUsers = User::whereIn('role', ['teacher', 'student'])->orderBy('name')->limit(200)->get(['id', 'name', 'email', 'role']);
        $this->ensureVipPlansExist();
        $vipPlans = VipPlan::orderBy('audience')->orderBy('sort_order')->get();
        $vipPromotions = Promotion::query()
            ->whereNotNull('vip_plan')
            ->latest()
            ->limit(8)
            ->get();

        return view('pages.admin.vip', compact('subscriptions', 'payments', 'summary', 'eligibleUsers', 'vipPlans', 'vipPromotions'));
    }

    public function updateVipPlan(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $plan = VipPlan::findOrFail($id);
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:0', 'max:100000000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        $plan->update($validated);

        return back()->with('success', 'ÄÃ£ cáº­p nháº­t giÃ¡ gÃ³i VIP.');
    }

    public function storeSubscription(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'plan' => ['required', Rule::in(['monthly', 'yearly', 'lifetime'])],
            'status' => ['required', Rule::in(['active', 'expired', 'cancelled'])],
            'started_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:started_at'],
        ]);

        abort_unless(User::whereKey($validated['user_id'])->whereIn('role', ['teacher', 'student'])->exists(), 422);

        $startedAt = isset($validated['started_at']) ? \Illuminate\Support\Carbon::parse($validated['started_at']) : now();
        VipSubscription::updateOrCreate(
            ['user_id' => $validated['user_id']],
            [
                'plan' => $validated['plan'],
                'status' => $validated['status'],
                'started_at' => $startedAt,
                'expires_at' => $this->vipSubscriptionExpiresAt($validated['plan'], $startedAt, $validated['expires_at'] ?? null),
            ]
        );

        return back()->with('success', 'ÄÃ£ cáº¥p quyá»n VIP.');
    }

    public function updateSubscription(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $subscription = VipSubscription::findOrFail($id);
        $validated = $request->validate([
            'plan' => ['required', Rule::in(['monthly', 'yearly', 'lifetime'])],
            'status' => ['required', Rule::in(['active', 'expired', 'cancelled'])],
            'started_at' => ['required', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:started_at'],
        ]);

        $startedAt = \Illuminate\Support\Carbon::parse($validated['started_at']);
        $subscription->update([
            'plan' => $validated['plan'],
            'status' => $validated['status'],
            'started_at' => $startedAt,
            'expires_at' => $this->vipSubscriptionExpiresAt($validated['plan'], $startedAt, $validated['expires_at'] ?? null),
        ]);
        return back()->with('success', 'ÄÃ£ cáº­p nháº­t gÃ³i VIP.');
    }

    public function updatePayment(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $request->validate(['status' => 'required|in:pending,paid,failed,cancelled']);
        $payment = VipPayment::findOrFail($id);
        if ($validated['status'] === 'paid') {
            $this->activateSubscriptionFromPayment($payment);
        } else {
            $payment->update(['status' => $validated['status']]);
        }

        return back()->with('success', 'ÄÃ£ cáº­p nháº­t thanh toÃ¡n.');
    }

    public function promotions(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $state = $request->query('state', 'active');

        $promotions = Promotion::query()
            ->when($state === 'all' || $state === 'deleted', fn ($query) => $query->withTrashed())
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->query('q'));
                $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%"));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('discount_type'), fn ($query) => $query->where('discount_type', $request->query('discount_type')))
            ->when($request->filled('vip_plan'), fn ($query) => $query->where('vip_plan', $request->query('vip_plan')))
            ->when($request->query('scope') === 'vip', fn ($query) => $query->whereNotNull('vip_plan'))
            ->when($request->query('scope') === 'general', fn ($query) => $query->whereNull('vip_plan'))
            ->when($request->query('scope') === 'running', fn ($query) => $query->where('status', 'active')
                ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit')))
            ->when($request->query('scope') === 'scheduled', fn ($query) => $query->where('status', 'active')->whereNotNull('starts_at')->where('starts_at', '>', now()))
            ->when($request->query('scope') === 'expired', fn ($query) => $query->whereNotNull('ends_at')->where('ends_at', '<', now()))
            ->when($request->query('scope') === 'exhausted', fn ($query) => $query->whereNotNull('usage_limit')->whereColumn('used_count', '>=', 'usage_limit'))
            ->when($request->query('state') === 'deleted', fn ($query) => $query->onlyTrashed())
            ->when($state === 'active', fn ($query) => $query->whereNull('deleted_at'))
            ->when($request->query('sort') === 'code', fn ($query) => $query->orderBy('code'))
            ->when($request->query('sort') === 'ending', fn ($query) => $query->orderByRaw('ends_at is null')->orderBy('ends_at'))
            ->when($request->query('sort') === 'usage', fn ($query) => $query->orderByDesc('used_count'))
            ->when($request->query('sort') === 'value', fn ($query) => $query->orderByDesc('discount_value'))
            ->when($request->query('sort') === 'oldest', fn ($query) => $query->oldest())
            ->when(! in_array($request->query('sort'), ['code', 'ending', 'usage', 'value', 'oldest'], true), fn ($query) => $query->latest())
            ->paginate(18)
            ->withQueryString();

        $summary = [
            'total' => Promotion::withTrashed()->count(),
            'active' => Promotion::where('status', 'active')->count(),
            'inactive' => Promotion::where('status', 'inactive')->count(),
            'running' => Promotion::where('status', 'active')
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->where(fn ($query) => $query->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
                ->count(),
            'scheduled' => Promotion::where('status', 'active')->whereNotNull('starts_at')->where('starts_at', '>', now())->count(),
            'expired' => Promotion::whereNotNull('ends_at')->where('ends_at', '<', now())->count(),
            'exhausted' => Promotion::whereNotNull('usage_limit')->whereColumn('used_count', '>=', 'usage_limit')->count(),
            'vip' => Promotion::whereNotNull('vip_plan')->count(),
            'deleted' => Promotion::onlyTrashed()->count(),
        ];

        return view('pages.admin.promotions', compact('promotions', 'summary'));
    }

    public function storePromotion(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        Promotion::create($this->validatePromotion($request));

        return back()->with('success', 'ÄÃ£ táº¡o khuyáº¿n mÃ£i.');
    }

    public function updatePromotion(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        Promotion::withTrashed()->findOrFail($id)->update($this->validatePromotion($request, $id));

        return back()->with('success', 'ÄÃ£ cáº­p nháº­t khuyáº¿n mÃ£i.');
    }

    public function deletePromotion(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        Promotion::findOrFail($id)->delete();

        return back()->with('success', 'ÄÃ£ Ä‘Æ°a khuyáº¿n mÃ£i vÃ o thÃ¹ng rÃ¡c.');
    }

    public function restorePromotion(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        Promotion::withTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'ÄÃ£ khÃ´i phá»¥c khuyáº¿n mÃ£i.');
    }
}

