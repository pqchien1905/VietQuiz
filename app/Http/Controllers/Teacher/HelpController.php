<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function index()
    {
        return view('pages.teacher.help');
    }

    public function submitTicket(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|in:technical,account,quiz,grading,other',
            'content' => 'required|string|min:10|max:2000',
        ]);

        // In production, this would create a support ticket
        // For now, just return success
        return back()->with('success', 'Đã gửi yêu cầu hỗ trợ! Chúng tôi sẽ phản hồi trong 24 giờ.');
    }
}
