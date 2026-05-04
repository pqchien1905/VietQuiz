<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterAsTeacherController extends Controller
{
    public function create(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'student') {
            abort(403, 'Chỉ tài khoản học sinh mới có thể đăng ký làm giáo viên.');
        }

        $user->forceFill([
            'role' => 'teacher',
            'can_switch_role' => true,
            'last_active_role' => 'teacher',
        ])->save();

        $targetUrl = route('teacher.dashboard');
        $intended = $request->query('intended');
        if (is_string($intended)) {
            $appUrl = $request->getSchemeAndHttpHost();
            if (str_starts_with($intended, $appUrl . '/teacher/') || str_starts_with($intended, '/teacher/')) {
                $targetUrl = $intended;
            }
        }

        return redirect()->to($targetUrl)
            ->with('success', 'Đã bật màn Giáo viên cho tài khoản hiện tại.');
    }
}
