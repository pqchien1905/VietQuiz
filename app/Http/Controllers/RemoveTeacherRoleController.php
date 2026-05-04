<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RemoveTeacherRoleController extends Controller
{
    public function create(Request $request)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'teacher') {
            abort(403, 'Chỉ tài khoản giáo viên mới có thể thực hiện thao tác này.');
        }

        $user->forceFill([
            'role' => 'student',
            'can_switch_role' => false,
            'last_active_role' => 'student',
        ])->save();

        return redirect()->route('student.dashboard')
            ->with('success', 'Đã tắt màn Giáo viên cho tài khoản hiện tại.');
    }
}
