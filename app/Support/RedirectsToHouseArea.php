<?php

namespace App\Support;

use App\Models\InspectionArea;
use App\Models\PropertyHouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait RedirectsToHouseArea
{
    protected function redirectToHouseArea(
        PropertyHouse $house,
        ?InspectionArea $area = null,
        ?string $status = null,
    ): RedirectResponse {
        $url = route('admin.houses.show', $house);
        if ($area) {
            $url .= '#area-' . $area->id;
        }

        $redirect = redirect($url);
        if ($status) {
            $redirect->with('status', $status);
        }

        return $redirect;
    }

    protected function areaJsonResponse(InspectionArea $area, string $status): JsonResponse
    {
        $area->refresh();

        return response()->json([
            'message' => $status,
            'area' => $this->serializeAreaForJson($area),
        ]);
    }

    protected function serializeAreaForJson(InspectionArea $area): array
    {
        $score = (int) $area->score;
        $label = 'ضعيف';
        $colorClass = 'bg-slate-700';

        if ($score >= 80) {
            $label = 'ممتاز';
            $colorClass = 'bg-green-500';
        } elseif ($score >= 70) {
            $label = 'جيد';
            $colorClass = 'bg-yellow-400';
        } elseif ($score >= 60) {
            $label = 'متوسط';
            $colorClass = 'bg-[#8b4513]';
        } else {
            $colorClass = 'bg-[#F70000]';
        }

        return [
            'id' => $area->id,
            'name' => $area->name,
            'score' => $score,
            'label' => $label,
            'color_class' => $colorClass,
            'notes' => $area->notesList(),
            'recommendations' => $area->recommendationsList(),
        ];
    }

    protected function wantsAreaJson(Request $request): bool
    {
        return $request->expectsJson() || $request->boolean('ajax');
    }
}
