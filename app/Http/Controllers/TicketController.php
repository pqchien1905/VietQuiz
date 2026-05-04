<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Support\VipFeature;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category'    => 'required|in:technical,account,quiz,grades,other',
            'subject'     => 'required|string|max:255',
            'description' => 'required|string|max:2000',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'open';
        $validated['priority'] = VipFeature::isVip($request->user()) ? 'vip' : 'normal';

        Ticket::create($validated);

        $message = $validated['priority'] === 'vip'
            ? 'Đã gửi yêu cầu hỗ trợ ưu tiên VIP thành công! Chúng tôi sẽ phản hồi trong ngày làm việc.'
            : 'Đã gửi yêu cầu hỗ trợ thành công! Chúng tôi sẽ phản hồi trong 24 giờ.';

        return back()->with('success', $message);
    }

    public function index(Request $request)
    {
        $route = $request->user()->isTeacher() ? 'teacher.help' : 'student.help';

        return redirect()->route($route);
    }
}
