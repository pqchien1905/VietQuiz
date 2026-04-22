<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = Notification::where('user_id', $user->id)
            ->latest()
            ->get();

        $unreadCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return view('pages.teacher.notifications', compact('notifications', 'unreadCount'));
    }

    public function markAsRead(Notification $notification)
    {
        $notification->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }

    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
        return back()->with('success', 'Đã đánh dấu tất cả đã đọc!');
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();
        return back()->with('success', 'Đã xóa thông báo!');
    }

    public function clearAll(Request $request)
    {
        Notification::where('user_id', $request->user()->id)->delete();
        return back()->with('success', 'Đã xóa tất cả thông báo!');
    }
}
