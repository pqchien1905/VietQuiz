<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        return view('pages.student.settings');
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $request->user()->id,
            'phone' => 'nullable|string|max:20',
        ]);

        $request->user()->update($validated);

        return back()->with('success', 'Đã lưu hồ sơ thành công!');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'        => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Đã cập nhật mật khẩu!');
    }

    public function updateNotifications(Request $request)
    {
        $request->session()->put('notif_email', $request->boolean('notif_email'));
        $request->session()->put('notif_push', $request->boolean('notif_push'));
        $request->session()->put('notif_quiz', $request->boolean('notif_quiz'));
        $request->session()->put('notif_assignment', $request->boolean('notif_assignment'));
        $request->session()->put('notif_grade', $request->boolean('notif_grade'));

        return back()->with('success', 'Đã lưu cài đặt thông báo!');
    }
}
