<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        return view('pages.teacher.settings', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'required|email|unique:users,email,' . $request->user()->id,
            'phone'  => 'nullable|string|max:20',
            'subject'=> 'nullable|string|max:100',
        ]);

        $request->user()->update($validated);

        return back()->with('success', 'Đã lưu hồ sơ thành công!');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Đã cập nhật mật khẩu!');
    }

    public function updateNotifications(Request $request)
    {
        // Store notification preferences in user preferences (via session/meta field)
        $request->session()->put('notif_email', $request->boolean('notif_email'));
        $request->session()->put('notif_push', $request->boolean('notif_push'));
        $request->session()->put('notif_submission', $request->boolean('notif_submission'));
        $request->session()->put('notif_deadline', $request->boolean('notif_deadline'));

        return back()->with('success', 'Đã lưu cài đặt thông báo!');
    }
}
