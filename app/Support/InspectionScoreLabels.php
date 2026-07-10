<?php

namespace App\Support;

final class InspectionScoreLabels
{
    /** نفس تقييم الأقسام وبداية التقرير */
    public static function label(int $score): string
    {
        $score = min(100, max(0, $score));

        if ($score >= 80) {
            return 'ممتاز';
        }
        if ($score >= 70) {
            return 'جيد';
        }
        if ($score >= 60) {
            return 'متوسط';
        }

        return 'ضعيف';
    }
}
