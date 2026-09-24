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

    public static function labelEn(int $score): string
    {
        $score = min(100, max(0, $score));

        if ($score >= 80) {
            return 'Excellent';
        }
        if ($score >= 70) {
            return 'Good';
        }
        if ($score >= 60) {
            return 'Average';
        }

        return 'Poor';
    }

    /** Map Arabic inspector override to English for EN reports. */
    public static function translateRating(?string $rating): ?string
    {
        if ($rating === null || trim($rating) === '') {
            return null;
        }

        return match (trim($rating)) {
            'ممتاز' => 'Excellent',
            'جيد جداً', 'جيد جدا' => 'Very Good',
            'جيد' => 'Good',
            'متوسط' => 'Average',
            'ضعيف' => 'Poor',
            default => $rating,
        };
    }
}
