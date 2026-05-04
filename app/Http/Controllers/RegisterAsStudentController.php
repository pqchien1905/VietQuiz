<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterAsStudentController extends Controller
{
    public function create(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'teacher') {
            abort(403, 'Chỉ màn giáo viên mới có thể bật màn học sinh.');
        }

        $targetUrl = route('student.dashboard');
        $intended = $request->query('intended');
        if (is_string($intended)) {
            $appUrl = $request->getSchemeAndHttpHost();
            if (str_starts_with($intended, $appUrl . '/student/') || str_starts_with($intended, '/student/')) {
                $targetUrl = $intended;
            }
        }

        $user->forceFill([
            'role' => 'student',
            'can_switch_role' => true,
            'last_active_role' => 'student',
        ])->save();

        return redirect()->to($targetUrl)
            ->with('success', 'Đã bật màn Học sinh cho tài khoản hiện tại.');
    }
}
