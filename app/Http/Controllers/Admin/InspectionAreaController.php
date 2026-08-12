<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InspectionArea;
use App\Models\PropertyHouse;
use App\Support\InspectionTextLists;
use App\Support\RedirectsToHouseArea;
use App\Support\ReportCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InspectionAreaController extends Controller
{
    use RedirectsToHouseArea;
    public function store(Request $request, PropertyHouse $house): RedirectResponse
    {
        if ($request->input('name') === '__custom__') {
            $request->merge([
                'name' => trim((string) $request->input('custom_section_name', '')),
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes_text' => ['nullable', 'string', 'max:20000'],
            'notes_list' => ['nullable'],
            'recommendations_text' => ['nullable', 'string', 'max:20000'],
            'recommendations_list' => ['nullable'],
        ], [
            'name.required' => 'يرجى اختيار أو كتابة "اسم القسم" قبل الحفظ.',
            'score.integer' => 'حقل "النسبة" يجب أن يكون رقماً صحيحاً.',
        ]);

        $rawNotes = $request->filled('notes_text') ? $data['notes_text'] : ($data['notes_list'] ?? []);
        $rawRecs = $request->filled('recommendations_text') ? $data['recommendations_text'] : ($data['recommendations_list'] ?? []);

        $notes = InspectionTextLists::normalize($rawNotes);
        $recs = InspectionTextLists::normalize($rawRecs);

        $max = (int) $house->inspectionAreas()->max('sort_order');
        $area = $house->inspectionAreas()->create([
            'name' => $data['name'],
            'score' => $data['score'] ?? 0,
            'notes_json' => $notes,
            'recommendations_json' => $recs,
            'additional_info' => InspectionTextLists::formatForStorage($notes),
            'recommendations' => InspectionTextLists::formatForStorage($recs),
            'sort_order' => $max + 1,
        ]);

        $this->normalizeSortOrder($house);
        $house->touch();
        ReportCache::clear($house);

        if ($this->wantsAreaJson($request)) {
            return $this->areaJsonResponse($area, 'تمت إضافة القسم.');
        }

        return $this->redirectToHouseArea($house, $area, 'تمت إضافة القسم.');
    }

    public function update(Request $request, PropertyHouse $house, InspectionArea $area): RedirectResponse|JsonResponse
    {
        $this->assertAreaBelongs($house, $area);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes_text' => ['nullable', 'string', 'max:20000'],
            'notes_list' => ['nullable'],
            'recommendations_text' => ['nullable', 'string', 'max:20000'],
            'recommendations_list' => ['nullable'],
        ], [
            'name.required' => 'يرجى اختيار أو كتابة "اسم القسم" قبل الحفظ.',
            'score.integer' => 'حقل "النسبة" يجب أن يكون رقماً صحيحاً.',
        ]);

        $rawNotes = $request->filled('notes_text') ? $data['notes_text'] : ($data['notes_list'] ?? []);
        $rawRecs = $request->filled('recommendations_text') ? $data['recommendations_text'] : ($data['recommendations_list'] ?? []);

        $notes = InspectionTextLists::normalize($rawNotes);
        $recs = InspectionTextLists::normalize($rawRecs);

        $area->update([
            'name' => $data['name'],
            'score' => $data['score'] ?? 0,
            'notes_json' => $notes,
            'recommendations_json' => $recs,
            'additional_info' => InspectionTextLists::formatForStorage($notes),
            'recommendations' => InspectionTextLists::formatForStorage($recs),
        ]);

        $house->touch();
        ReportCache::clear($house);

        if ($this->wantsAreaJson($request)) {
            return $this->areaJsonResponse($area, 'تم تحديث بيانات القسم.');
        }

        return $this->redirectToHouseArea($house, $area, 'تم تحديث بيانات القسم.');
    }

    public function destroy(Request $request, PropertyHouse $house, InspectionArea $area): RedirectResponse|JsonResponse
    {
        $this->assertAreaBelongs($house, $area);

        $areaId = $area->id;
        $area->delete();

        $this->normalizeSortOrder($house);
        $house->touch();
        ReportCache::clear($house);

        if ($this->wantsAreaJson($request)) {
            return response()->json([
                'message' => 'تم حذف القسم.',
                'deleted_area_id' => $areaId,
            ]);
        }

        return $this->redirectToHouseArea($house, null, 'تم حذف القسم.');
    }

    public function reorder(Request $request, PropertyHouse $house, InspectionArea $area): RedirectResponse|JsonResponse
    {
        $this->assertAreaBelongs($house, $area);

        $data = $request->validate([
            'sort_order' => ['required', 'integer', 'min:1'],
        ]);

        $newOrder = (int) $data['sort_order'];
        
        // Get all areas in current order
        $areas = $house->inspectionAreas()->orderBy('sort_order')->get();
        
        // Filter out the area we are moving
        $others = $areas->reject(fn($item) => $item->id === $area->id)->values();
        
        // Determine the target index (0-based)
        $targetIndex = max(0, min($newOrder - 1, $others->count()));
        
        // Splice the area back in at the target position
        $others->splice($targetIndex, 0, [$area]);
        
        // Update all areas with their new sequential sort_order
        foreach ($others as $index => $item) {
            $item->update(['sort_order' => $index + 1]);
        }

        $house->touch();
        ReportCache::clear($house);

        if ($this->wantsAreaJson($request)) {
            return $this->areaJsonResponse($area, 'تم تحديث الترتيب.');
        }

        return $this->redirectToHouseArea($house, $area, 'تم تحديث الترتيب.');
    }

    private function normalizeSortOrder(PropertyHouse $house): void
    {
        // Order by sort_order first, then by updated_at desc to favor the recently changed item
        $areas = $house->inspectionAreas()
            ->orderBy('sort_order')
            ->orderBy('updated_at', 'desc')
            ->get();

        foreach ($areas as $i => $area) {
            $newOrder = $i + 1;
            if ((int) $area->sort_order !== $newOrder) {
                $area->update(['sort_order' => $newOrder]);
            }
        }
    }

    private function assertAreaBelongs(PropertyHouse $house, InspectionArea $area): void
    {
        if ((int) $area->property_house_id !== (int) $house->id) {
            abort(404);
        }
    }
}
