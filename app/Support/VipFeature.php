<?php

namespace App\Support;

use App\Models\User;

class VipFeature
{
    public const FREE_CLASS_LIMIT = 3;
    public const FREE_BANK_QUESTION_LIMIT = 50;
    public const FREE_QUIZ_QUESTION_LIMIT = 50;

    public static function isVip(User $user): bool
    {
        return (bool) $user->vipSubscription?->is_active;
    }

    public static function canCreateClass(User $user): bool
    {
        return self::isVip($user) || $user->createdClasses()->count() < self::FREE_CLASS_LIMIT;
    }

    public static function canAddBankQuestions(User $user, int $newQuestions = 1): bool
    {
        if (self::isVip($user)) {
            return true;
        }

        $current = $user->questions()->whereNull('quiz_id')->count();

        return ($current + $newQuestions) <= self::FREE_BANK_QUESTION_LIMIT;
    }

    public static function canUseQuizQuestionCount(User $user, int $questionCount): bool
    {
        return self::isVip($user) || $questionCount <= self::FREE_QUIZ_QUESTION_LIMIT;
    }

    public static function classLimitMessage(): string
    {
        return 'Gói miễn phí chỉ tạo tối đa ' . self::FREE_CLASS_LIMIT . ' lớp học. Vui lòng nâng cấp Pro để tạo không giới hạn.';
    }

    public static function bankQuestionLimitMessage(): string
    {
        return 'Gói miễn phí chỉ lưu tối đa ' . self::FREE_BANK_QUESTION_LIMIT . ' câu hỏi trong ngân hàng. Vui lòng nâng cấp Pro để dùng không giới hạn.';
    }

    public static function quizQuestionLimitMessage(): string
    {
        return 'Gói miễn phí chỉ tạo tối đa ' . self::FREE_QUIZ_QUESTION_LIMIT . ' câu hỏi mỗi đề. Vui lòng nâng cấp Pro để tạo đề không giới hạn câu hỏi.';
    }

    public static function aiMessage(): string
    {
        return 'Tạo câu hỏi bằng AI là tính năng Pro. Vui lòng nâng cấp để sử dụng.';
    }

    public static function aiGradingMessage(): string
    {
        return 'Chấm điểm bằng AI là tính năng Pro. Vui lòng nâng cấp để sử dụng.';
    }

    public static function exportMessage(): string
    {
        return 'Xuất báo cáo Excel/PDF là tính năng Pro. Vui lòng nâng cấp để sử dụng.';
    }
}
