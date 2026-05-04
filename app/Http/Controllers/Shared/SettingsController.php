<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $viewPath = $user->isTeacher() ? 'pages.teacher.settings' : 'pages.student.settings';
        $activeTab = old('settings_tab', session('settings_tab', 'profile'));
        $notificationSettings = [
            'notif_email' => $request->session()->get('notif_email', true),
            'notif_push' => $request->session()->get('notif_push', false),
            'notif_quiz' => $request->session()->get('notif_quiz', true),
            'notif_assignment' => $request->session()->get('notif_assignment', true),
            'notif_grade' => $request->session()->get('notif_grade', true),
        ];
        $currentSession = [
            'device' => $request->userAgent() ?: 'Trình duyệt hiện tại',
            'ip' => $request->ip(),
            'last_active' => now()->format('d/m/Y H:i'),
        ];

        return view($viewPath, compact('user', 'activeTab', 'notificationSettings', 'currentSession'));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $rules = [
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'phone'         => 'nullable|string|max:20',
            'avatar'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_avatar' => 'nullable|boolean',
        ];

        if ($user->isTeacher()) {
            $rules['subject'] = 'nullable|string|max:100';
        }

        $validated = $request->validate($rules);
        $profileData = collect($validated)->only(['name', 'email', 'phone', 'subject'])->all();

        if ($request->boolean('remove_avatar') && $user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $profileData['avatar'] = null;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $profileData['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($profileData);

        return back()
            ->with('success', 'Đã lưu hồ sơ thành công!')
            ->with('settings_tab', 'profile');
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

        return back()
            ->with('success', 'Đã cập nhật mật khẩu!')
            ->with('settings_tab', 'security');
    }

    public function updateNotifications(Request $request)
    {
        $user = $request->user();

        if ($user->isTeacher()) {
            $request->session()->put('notif_email', $request->boolean('notif_email'));
            $request->session()->put('notif_push', $request->boolean('notif_push'));
            $request->session()->put('notif_submission', $request->boolean('notif_submission'));
            $request->session()->put('notif_deadline', $request->boolean('notif_deadline'));
        } else {
            $request->session()->put('notif_email', $request->boolean('notif_email'));
            $request->session()->put('notif_push', $request->boolean('notif_push'));
            $request->session()->put('notif_quiz', $request->boolean('notif_quiz'));
            $request->session()->put('notif_assignment', $request->boolean('notif_assignment'));
            $request->session()->put('notif_grade', $request->boolean('notif_grade'));
        }

        return back()
            ->with('success', 'Đã lưu cài đặt thông báo!')
            ->with('settings_tab', 'notifications');
    }

    public function exportData(Request $request)
    {
        $user = $request->user();

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'classes' => $user->classes()
                ->with('teacher:id,name,email')
                ->get()
                ->map(fn ($class) => [
                    'name' => $class->name,
                    'code' => $class->code,
                    'teacher' => $class->teacher?->name,
                    'joined_at' => $class->pivot?->joined_at,
                ])
                ->values(),
            'courses' => $user->courses()
                ->with('teacher:id,name,email')
                ->get()
                ->map(fn ($course) => [
                    'name' => $course->name,
                    'teacher' => $course->teacher?->name,
                    'enrolled_at' => $course->pivot?->enrolled_at,
                ])
                ->values(),
            'quiz_attempts' => $user->quizAttempts()
                ->whereNotNull('submitted_at')
                ->get()
                ->map(fn ($quiz) => [
                    'title' => $quiz->title,
                    'score' => $quiz->pivot?->score,
                    'total_points' => $quiz->pivot?->total_points,
                    'submitted_at' => $quiz->pivot?->submitted_at,
                ])
                ->values(),
            'submissions' => $user->submissions()
                ->with('assignment:id,title,total_points')
                ->get()
                ->map(fn ($submission) => [
                    'assignment' => $submission->assignment?->title,
                    'submitted_at' => $submission->submitted_at?->toIso8601String(),
                ])
                ->values(),
            'grades' => DB::table('grades')
                ->where('student_id', $user->id)
                ->select(['gradable_type', 'gradable_id', 'score', 'feedback', 'graded_at'])
                ->get(),
        ];

        $filename = 'vietquiz_student_data_' . now()->format('Ymd_His') . '.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, ['Content-Type' => 'application/json; charset=UTF-8']);
    }
}
