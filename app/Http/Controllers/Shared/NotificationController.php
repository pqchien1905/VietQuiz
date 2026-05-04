<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $filter = $request->query('filter', 'all');
        $search = trim((string) $request->query('q', ''));
        $allowedFilters = ['all', 'unread', 'read', 'assignment', 'quiz', 'grading', 'class', 'system'];

        if (! in_array($filter, $allowedFilters, true)) {
            $filter = 'all';
        }

        $query = Notification::where('user_id', $user->id)->latest();

        if ($filter === 'unread') {
            $query->where('is_read', false);
        } elseif ($filter === 'read') {
            $query->where('is_read', true);
        } elseif ($filter !== 'all') {
            $query->whereIn('type', $this->categoryTypes($filter));
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        $notifications = $query->paginate(10)->withQueryString();
        $unreadCount = Notification::where('user_id', $user->id)->where('is_read', false)->count();
        $totalCount = Notification::where('user_id', $user->id)->count();
        $readCount = max(0, $totalCount - $unreadCount);
        $typeCounts = Notification::where('user_id', $user->id)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $viewPath = $user->isTeacher() ? 'pages.teacher.notifications' : 'pages.student.notifications';

        return view($viewPath, [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'totalCount' => $totalCount,
            'readCount' => $readCount,
            'typeCounts' => $typeCounts,
            'currentFilter' => $filter,
            'search' => $search,
            'categoryCounts' => [
                'assignment' => $this->countCategory($typeCounts, 'assignment'),
                'quiz' => $this->countCategory($typeCounts, 'quiz'),
                'grading' => $this->countCategory($typeCounts, 'grading'),
                'class' => $this->countCategory($typeCounts, 'class'),
                'system' => $this->countCategory($typeCounts, 'system'),
            ],
        ]);
    }

    public function markAsRead(Request $request, Notification $notification)
    {
        $this->authorizeNotification($request, $notification);
        $notification->update(['is_read' => true]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Đã đánh dấu thông báo là đã đọc.');
    }

    public function markAsUnread(Request $request, Notification $notification)
    {
        $this->authorizeNotification($request, $notification);
        $notification->update(['is_read' => false]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Đã đánh dấu thông báo là chưa đọc.');
    }

    public function open(Request $request, Notification $notification)
    {
        $this->authorizeNotification($request, $notification);
        $notification->update(['is_read' => true]);

        return redirect()->to($this->notificationUrl($request, $notification)
            ?? route($request->user()->isTeacher() ? 'teacher.notifications' : 'student.notifications'));
    }

    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    }

    public function clearAll(Request $request)
    {
        Notification::where('user_id', $request->user()->id)->delete();

        return back()->with('success', 'Đã xóa tất cả thông báo.');
    }

    public function destroy(Request $request, Notification $notification)
    {
        $this->authorizeNotification($request, $notification);
        $notification->delete();

        return back()->with('success', 'Đã xóa thông báo.');
    }

    private function authorizeNotification(Request $request, Notification $notification): void
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);
    }

    private function categoryTypes(string $category): array
    {
        return match ($category) {
            'assignment' => ['assignment', 'assignment_assigned', 'assignment_due', 'assignment_submitted'],
            'quiz' => ['quiz', 'quiz_assigned', 'quiz_result'],
            'grading' => ['grading', 'grade', 'grade_published', 'submission'],
            'class' => ['class', 'class_announcement', 'class_joined', 'course', 'course_assigned'],
            'system' => ['system', 'reminder', 'vip', 'account'],
            default => [],
        };
    }

    private function countCategory($typeCounts, string $category): int
    {
        return collect($this->categoryTypes($category))
            ->sum(fn (string $type) => (int) ($typeCounts[$type] ?? 0));
    }

    private function notificationData(Notification $notification): array
    {
        $data = $notification->data;

        if (is_string($data)) {
            $decoded = json_decode($data, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($data) ? $data : [];
    }

    private function notificationUrl(Request $request, Notification $notification): ?string
    {
        $user = $request->user();
        $data = $this->notificationData($notification);

        if (isset($data['url']) && is_string($data['url']) && Str::startsWith($data['url'], '/') && ! Str::startsWith($data['url'], '//')) {
            return $data['url'];
        }

        if ($user->isTeacher()) {
            if (! empty($data['class_id'])) {
                return route('teacher.class-detail', $data['class_id']);
            }

            if (! empty($data['quiz_id'])) {
                return route('teacher.quiz-detail', $data['quiz_id']);
            }

            if (Str::contains($notification->type, ['grade', 'grading', 'submission'])) {
                return route('teacher.grading');
            }

            if (Str::contains($notification->type, 'assignment')) {
                return route('teacher.assignments');
            }

            if (Str::contains($notification->type, 'class')) {
                return route('teacher.classes');
            }

            if (Str::contains($notification->type, 'quiz')) {
                return route('teacher.quizzes');
            }

            return null;
        }

        if (! empty($data['assignment_id'])) {
            return route('student.assignment-detail', $data['assignment_id']);
        }

        if (! empty($data['quiz_id']) && ! Str::contains($notification->type, ['grade', 'result'])) {
            return route('student.quiz-take', $data['quiz_id']);
        }

        if (Str::contains($notification->type, ['grade', 'result'])) {
            return route('student.grades');
        }

        if (Str::contains($notification->type, 'assignment')) {
            return route('student.assignments');
        }

        if (Str::contains($notification->type, 'class')) {
            return route('student.classes');
        }

        if (Str::contains($notification->type, 'course')) {
            return route('student.courses');
        }

        if (Str::contains($notification->type, 'quiz')) {
            return route('student.quizzes');
        }

        return null;
    }
}
