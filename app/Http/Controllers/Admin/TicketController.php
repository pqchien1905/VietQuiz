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

class TicketController extends AdminBaseController
{
    public function notifications(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $state = $request->query('state', 'active');

        $notifications = Notification::query()
            ->when($state === 'all' || $state === 'deleted', fn ($query) => $query->withTrashed())
            ->with('user')
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->query('q'));
                $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")));
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->query('type')))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->query('user_id')))
            ->when($request->filled('role'), fn ($query) => $query->whereHas('user', fn ($q) => $q->where('role', $request->query('role'))))
            ->when($request->query('state') === 'read', fn ($query) => $query->where('is_read', true))
            ->when($request->query('state') === 'unread', fn ($query) => $query->where('is_read', false))
            ->when($request->query('state') === 'deleted', fn ($query) => $query->onlyTrashed())
            ->when($state === 'active', fn ($query) => $query->whereNull('deleted_at'))
            ->when($request->query('scope') === 'with_url', fn ($query) => $query->whereNotNull('data->url'))
            ->when($request->query('scope') === 'system', fn ($query) => $query->whereIn('type', ['admin_broadcast', 'system', 'vip']))
            ->when($request->query('scope') === 'learning', fn ($query) => $query->whereIn('type', ['quiz', 'assignment', 'grade', 'grading', 'class', 'course', 'reminder']))
            ->when($request->query('sort') === 'oldest', fn ($query) => $query->oldest())
            ->when($request->query('sort') === 'recipient', fn ($query) => $query->orderBy(User::select('name')->whereColumn('users.id', 'notifications.user_id')))
            ->when($request->query('sort') === 'type', fn ($query) => $query->orderBy('type')->latest())
            ->when(! in_array($request->query('sort'), ['oldest', 'recipient', 'type'], true), fn ($query) => $query->latest())
            ->paginate(18)
            ->withQueryString();
        $users = User::orderBy('name')->limit(200)->get(['id', 'name', 'email', 'role']);
        $types = Notification::query()->select('type')->distinct()->orderBy('type')->pluck('type')->filter()->values();
        $summary = [
            'total' => Notification::withTrashed()->count(),
            'active' => Notification::count(),
            'unread' => Notification::where('is_read', false)->count(),
            'read' => Notification::where('is_read', true)->count(),
            'deleted' => Notification::onlyTrashed()->count(),
            'broadcast' => Notification::where('type', 'admin_broadcast')->count(),
            'today' => Notification::whereDate('created_at', today())->count(),
        ];

        return view('pages.admin.notifications', compact('notifications', 'users', 'types', 'summary'));
    }

    public function storeNotification(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $request->validate([
            'target' => ['required', 'in:user,teacher,student,all'],
            'user_id' => ['nullable', 'required_if:target,user', 'exists:users,id'],
            'type' => ['nullable', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:3000'],
            'url' => ['nullable', 'string', 'max:1000'],
        ]);

        $query = User::query();
        if ($validated['target'] === 'user') {
            $query->where('id', $validated['user_id']);
        } elseif (in_array($validated['target'], ['teacher', 'student'], true)) {
            $query->where('role', $validated['target']);
        }

        $count = 0;
        $query->chunkById(100, function ($users) use ($validated, &$count) {
            foreach ($users as $user) {
                Notification::create([
                    'user_id' => $user->id,
                    'type' => $validated['type'] ?: 'admin_broadcast',
                    'title' => $validated['title'],
                    'body' => $validated['body'] ?? null,
                    'data' => array_filter([
                        'target' => $validated['target'],
                        'url' => $validated['url'] ?? null,
                        'created_by' => 'admin',
                    ]),
                    'is_read' => false,
                ]);
                $count++;
            }
        });

        return back()->with('success', "ÄÃ£ gá»­i {$count} thÃ´ng bÃ¡o.");
    }

    public function deleteNotification(Request $request, string $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        Notification::findOrFail($id)->delete();

        return back()->with('success', 'ÄÃ£ Ä‘Æ°a thÃ´ng bÃ¡o vÃ o thÃ¹ng rÃ¡c.');
    }

    public function updateNotificationReadState(Request $request, string $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $request->validate([
            'is_read' => ['required', 'boolean'],
        ]);

        Notification::withTrashed()->findOrFail($id)->update(['is_read' => $validated['is_read']]);

        return back()->with('success', 'ÄÃ£ cáº­p nháº­t tráº¡ng thÃ¡i Ä‘á»c.');
    }

    public function restoreNotification(Request $request, string $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        Notification::withTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'ÄÃ£ khÃ´i phá»¥c thÃ´ng bÃ¡o.');
    }

    public function tickets(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $tickets = Ticket::with('user')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->query('category')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->query('priority')))
            ->when($request->filled('role'), fn ($query) => $query->whereHas('user', fn ($q) => $q->where('role', $request->query('role'))))
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->query('q'));
                $query->where(fn ($q) => $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('admin_response', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")));
            })
            ->when($request->query('scope') === 'unanswered', fn ($query) => $query->whereNull('admin_response'))
            ->when($request->query('scope') === 'answered', fn ($query) => $query->whereNotNull('admin_response'))
            ->when($request->query('scope') === 'active', fn ($query) => $query->whereIn('status', ['open', 'in_progress']))
            ->when($request->query('scope') === 'stale', fn ($query) => $query->whereIn('status', ['open', 'in_progress'])->where('updated_at', '<=', now()->subDays(2)))
            ->when($request->query('scope') === 'vip', fn ($query) => $query->where('priority', 'vip'))
            ->when($request->query('sort') === 'oldest', fn ($query) => $query->oldest())
            ->when($request->query('sort') === 'updated', fn ($query) => $query->orderByDesc('updated_at'))
            ->when($request->query('sort') === 'sender', fn ($query) => $query->orderBy(User::select('name')->whereColumn('users.id', 'tickets.user_id')))
            ->when($request->query('sort') === 'priority', fn ($query) => $query->orderByRaw("CASE WHEN priority = 'vip' THEN 0 ELSE 1 END")->latest())
            ->when(! in_array($request->query('sort'), ['oldest', 'updated', 'sender', 'priority'], true), fn ($query) => $query->orderByRaw("CASE WHEN status = 'open' THEN 0 WHEN status = 'in_progress' THEN 1 WHEN status = 'resolved' THEN 2 ELSE 3 END")->orderByRaw("CASE WHEN priority = 'vip' THEN 0 ELSE 1 END")->latest())
            ->paginate(18)
            ->withQueryString();

        $summary = [
            'open' => Ticket::where('status', 'open')->count(),
            'in_progress' => Ticket::where('status', 'in_progress')->count(),
            'resolved' => Ticket::where('status', 'resolved')->count(),
            'closed' => Ticket::where('status', 'closed')->count(),
            'vip' => Ticket::where('priority', 'vip')->whereIn('status', ['open', 'in_progress'])->count(),
            'unanswered' => Ticket::whereIn('status', ['open', 'in_progress'])->whereNull('admin_response')->count(),
            'stale' => Ticket::whereIn('status', ['open', 'in_progress'])->where('updated_at', '<=', now()->subDays(2))->count(),
            'today' => Ticket::whereDate('created_at', today())->count(),
        ];
        $categories = [
            'technical' => 'Ká»¹ thuáº­t',
            'account' => 'TÃ i khoáº£n',
            'quiz' => 'BÃ i kiá»ƒm tra',
            'grades' => 'Äiá»ƒm sá»‘',
            'classes' => 'Lá»›p há»c',
            'quizzes' => 'BÃ i kiá»ƒm tra',
            'questions' => 'NgÃ¢n hÃ ng cÃ¢u há»i',
            'grading' => 'Cháº¥m Ä‘iá»ƒm',
            'students' => 'Há»c sinh',
            'analytics' => 'BÃ¡o cÃ¡o',
            'other' => 'KhÃ¡c',
        ];

        return view('pages.admin.tickets', compact('tickets', 'summary', 'categories'));
    }

    public function respondTicket(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) return $redirect;

        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
            'priority' => 'nullable|in:normal,vip',
            'category' => 'nullable|string|max:80',
            'admin_response' => 'nullable|string|max:3000',
        ]);

        $ticket = Ticket::findOrFail($id);
        $previousResponse = $ticket->admin_response;
        $ticket->update($validated);

        if (! empty($validated['admin_response']) && $validated['admin_response'] !== $previousResponse) {
            Notification::create([
                'user_id' => $ticket->user_id,
                'type' => 'support_ticket',
                'title' => 'YÃªu cáº§u há»— trá»£ Ä‘Ã£ Ä‘Æ°á»£c pháº£n há»“i',
                'body' => "YÃªu cáº§u \"{$ticket->subject}\" Ä‘Ã£ cÃ³ pháº£n há»“i tá»« quáº£n trá»‹ viÃªn.",
                'data' => [
                    'ticket_id' => $ticket->id,
                    'status' => $ticket->status,
                    'url' => route($ticket->user?->isTeacher() ? 'teacher.help' : 'student.help'),
                ],
                'is_read' => false,
            ]);
        }

        return back()->with('success', 'ÄÃ£ cáº­p nháº­t yÃªu cáº§u há»— trá»£.');
    }
}

