<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function index()
    {
        return view('pages.student.help');
    }

    public function submitTicket(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|in:quiz,assignment,login,grades,other',
            'content' => 'required|string|min:10|max:2000',
        ]);

        return back()->with('success', 'Đã gửi yêu cầu hỗ trợ! Chúng tôi sẽ phản hồi trong 24 giờ.');
    }
}
