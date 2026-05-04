<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Support\VipFeature;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $viewPath = $user->isTeacher() ? 'pages.teacher.help' : 'pages.student.help';
        $ticketQuery = $user->tickets();
        $tickets = (clone $ticketQuery)
            ->latest()
            ->limit(5)
            ->get();
        $statusCounts = (clone $ticketQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $ticketStats = collect(['open', 'in_progress', 'resolved', 'closed'])
            ->mapWithKeys(fn ($status) => [$status => (int) ($statusCounts[$status] ?? 0)])
            ->all();

        return view($viewPath, compact('tickets', 'ticketStats'));
    }

    public function submitTicket(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'category' => 'required|in:technical,account,quiz,grades,other',
            'subject' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'content' => 'nullable|string|max:2000',
        ]);

        $description = $validated['description'] ?? $validated['content'] ?? null;
        if (!is_string($description) || trim($description) === '') {
            return back()
                ->withErrors(['description' => 'Vui lòng nhập nội dung yêu cầu hỗ trợ.'])
                ->withInput();
        }

        Ticket::create([
            'user_id' => $user->id,
            'category' => $validated['category'],
            'subject' => $validated['subject'] ?? 'Yêu cầu hỗ trợ',
            'description' => $description,
            'status' => 'open',
            'priority' => VipFeature::isVip($user) ? 'vip' : 'normal',
        ]);

        return back()->with(
            'success',
            VipFeature::isVip($user)
                ? 'Đã gửi yêu cầu hỗ trợ ưu tiên VIP thành công! Chúng tôi sẽ phản hồi trong ngày làm việc.'
                : 'Đã gửi yêu cầu hỗ trợ thành công! Chúng tôi sẽ phản hồi trong 24 giờ.'
        );
    }
}
