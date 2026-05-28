<?php

namespace App\Support;

class AdminLabels
{
    public static function role(?string $role): string
    {
        return [
            'admin' => 'Quản trị viên',
            'teacher' => 'Giáo viên',
            'student' => 'Học sinh',
        ][$role] ?? (string) $role;
    }

    public static function status(?string $status): string
    {
        return [
            'active' => 'Hoạt động',
            'archived' => 'Đã lưu trữ',
            'draft' => 'Bản nháp',
            'published' => 'Đã xuất bản',
            'closed' => 'Đã đóng',
            'open' => 'Mới gửi',
            'in_progress' => 'Đang xử lý',
            'resolved' => 'Đã xử lý',
            'pending' => 'Chờ xử lý',
            'paid' => 'Đã thanh toán',
            'failed' => 'Thất bại',
            'cancelled' => 'Đã hủy',
            'expired' => 'Hết hạn',
            'inactive' => 'Tạm dừng',
            'deleted' => 'Đã xóa',
            'unread' => 'Chưa đọc',
            'submitted' => 'Đã nộp',
        ][$status] ?? (string) $status;
    }

    public static function questionType(?string $type): string
    {
        return [
            'multiple_choice' => 'Trắc nghiệm',
            'true_false' => 'Đúng/Sai',
            'short_answer' => 'Trả lời ngắn',
        ][$type] ?? (string) $type;
    }

    public static function assignmentType(?string $type): string
    {
        return [
            'file' => 'Tệp đính kèm',
            'text' => 'Văn bản',
            'online' => 'Trực tuyến',
        ][$type] ?? (string) $type;
    }

    public static function vipPlan(?string $plan): string
    {
        return [
            'monthly' => 'Hàng tháng',
            'yearly' => 'Hàng năm',
            'lifetime' => 'Trọn đời',
        ][$plan] ?? (string) $plan;
    }

    public static function discountType(?string $type): string
    {
        return [
            'percentage' => 'Phần trăm',
            'fixed' => 'Số tiền cố định',
        ][$type] ?? (string) $type;
    }

    public static function notificationType(?string $type): string
    {
        return [
            'admin_broadcast' => 'Thông báo hệ thống',
            'support_ticket' => 'Ticket hỗ trợ',
            'admin_test' => 'Thông báo kiểm thử',
        ][$type] ?? (string) $type;
    }

    public static function gradableType(?string $type): string
    {
        $shortName = class_basename((string) $type);

        return [
            'Submission' => 'Bài nộp',
            'Assignment' => 'Bài tập',
            'Quiz' => 'Bài kiểm tra',
        ][$shortName] ?? $shortName;
    }

    public static function summary(?string $key): string
    {
        return [
            'total' => 'Tổng cộng',
            'active' => 'Đang hoạt động',
            'inactive' => 'Tạm dừng',
            'deleted' => 'Đã xóa',
            'teachers' => 'Giáo viên',
            'students' => 'Học sinh',
            'bank' => 'Ngân hàng',
            'quiz' => 'Trong bài kiểm tra',
        ][$key] ?? self::status($key);
    }


}
