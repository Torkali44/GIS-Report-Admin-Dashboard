<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PropertyHouse;
use App\Services\InspectionReportPdfGenerator;
use App\Support\ReportCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PropertyHouseController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');

        $houses = PropertyHouse::query()
            ->withCount(['inspectionAreas'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('client_name', 'like', "%{$search}%")
                        ->orWhere('buyer_name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('reference_code', 'like', "%{$search}%")
                        ->orWhere('villa_number', 'like', "%{$search}%")
                        ->orWhere('compound', 'like', "%{$search}%")
                        ->orWhere('area', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.houses.index', compact('houses', 'search'));
    }

    public function create(): View
    {
        return view('admin.houses.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'reference_code' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:8000'],
            'activity' => ['nullable', 'string', 'max:255'],
            'property_type' => ['nullable', 'string', 'max:255'],
            'building_status' => ['nullable', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:255'],
            'intro_number' => ['nullable', 'string', 'max:255'],
            'villa_number' => ['nullable', 'string', 'max:255'],
            'road' => ['nullable', 'string', 'max:255'],
            'compound' => ['nullable', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
            'buyer_name' => ['nullable', 'string', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:255'],
            'developer_name' => ['nullable', 'string', 'max:255'],
            'engineering_supervisor' => ['nullable', 'string', 'max:255'],
            'main_contractor' => ['nullable', 'string', 'max:255'],
            'property_age' => ['nullable', 'string', 'max:255'],
            'land_area' => ['nullable', 'string', 'max:255'],
            'building_area' => ['nullable', 'string', 'max:255'],
            'floors_count' => ['nullable', 'string', 'max:255'],
            'rooms_count' => ['nullable', 'string', 'max:255'],
            'bathrooms_count' => ['nullable', 'string', 'max:255'],
            'halls_count' => ['nullable', 'string', 'max:255'],
            'parking_count' => ['nullable', 'string', 'max:255'],
            'kitchens_count' => ['nullable', 'string', 'max:255'],
            'total_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'final_result_text' => ['nullable', 'string', 'max:20000'],
            'final_general_notes' => ['nullable', 'string', 'max:8000'],
            'report_delivered_at' => ['nullable', 'date'],
        ]);

        $house = PropertyHouse::create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.houses.show', $house)
            ->with('status', 'تم إنشاء المنزل. أضف بيانات الأقسام الآن.');
    }

    public function edit(PropertyHouse $house): View
    {
        return view('admin.houses.edit', compact('house'));
    }

    public function update(Request $request, PropertyHouse $house): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'reference_code' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:8000'],
            'activity' => ['nullable', 'string', 'max:255'],
            'property_type' => ['nullable', 'string', 'max:255'],
            'building_status' => ['nullable', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:255'],
            'intro_number' => ['nullable', 'string', 'max:255'],
            'villa_number' => ['nullable', 'string', 'max:255'],
            'road' => ['nullable', 'string', 'max:255'],
            'compound' => ['nullable', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
            'buyer_name' => ['nullable', 'string', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:255'],
            'developer_name' => ['nullable', 'string', 'max:255'],
            'engineering_supervisor' => ['nullable', 'string', 'max:255'],
            'main_contractor' => ['nullable', 'string', 'max:255'],
            'property_age' => ['nullable', 'string', 'max:255'],
            'land_area' => ['nullable', 'string', 'max:255'],
            'building_area' => ['nullable', 'string', 'max:255'],
            'floors_count' => ['nullable', 'string', 'max:255'],
            'rooms_count' => ['nullable', 'string', 'max:255'],
            'bathrooms_count' => ['nullable', 'string', 'max:255'],
            'halls_count' => ['nullable', 'string', 'max:255'],
            'parking_count' => ['nullable', 'string', 'max:255'],
            'kitchens_count' => ['nullable', 'string', 'max:255'],
            'total_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'final_result_text' => ['nullable', 'string', 'max:20000'],
            'final_general_notes' => ['nullable', 'string', 'max:8000'],
            'report_delivered_at' => ['nullable', 'date'],
        ]);

        $house->update($data);
        ReportCache::clear($house);

        return redirect()
            ->route('admin.houses.index')
            ->with('status', 'تم تحديث بيانات المنزل.');
    }

    public function updateFinalResult(Request $request, PropertyHouse $house): RedirectResponse
    {
        $data = $request->validate([
            'final_result_text' => ['nullable', 'string', 'max:20000'],
            'final_general_notes' => ['nullable', 'string', 'max:8000'],
            'report_delivered_at' => ['nullable', 'date'],
        ]);

        $house->update($data);
        ReportCache::clear($house);

        return redirect()
            ->route('admin.houses.show', $house)
            ->with('status', 'تم حفظ النتيجة النهائية.');
    }

    public function show(PropertyHouse $house): View
    {
        $house->load([
            'inspectionAreas' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
        ]);

        $reportNo = $house->reference_code ?: ('H-' . $house->id);
        $reportDate = ($house->created_at ?: now())->format('Y-m-d');
        $clientName = trim((string) ($house->buyer_name ?? $house->client_name ?? '')) ?: '---';
        $propertyAddress = collect([
            $house->villa_number ? 'فيلا ' . $house->villa_number : null,
            $house->road ? 'طريق ' . $house->road : null,
            $house->compound ? 'مجمع ' . $house->compound : null,
            $house->area ?: null,
        ])->filter()->implode('  ') ?: ($house->address ?: $house->title);

        $categories = \App\Models\NoteCategory::with(['readyNotes', 'recommendationTemplates'])->orderBy('sort_order')->get();
        $categoriesJson = $categories->toJson();
        $readySections = \App\Models\ReadySection::with('noteCategory')->orderBy('sort_order')->get();
        $readySectionsJson = $readySections->toJson();

        return view('admin.houses.show', compact(
            'house',
            'categories',
            'categoriesJson',
            'readySections',
            'readySectionsJson',
            'reportNo',
            'reportDate',
            'clientName',
            'propertyAddress',
        ));
    }

    public function destroy(PropertyHouse $house): RedirectResponse
    {
        $house->delete();

        return redirect()
            ->route('admin.houses.index')
            ->with('status', 'تم حذف المنزل وجميع المرفقات.');
    }

    public function report(Request $request, PropertyHouse $house, InspectionReportPdfGenerator $generator): Response
    {
        $filename = 'inspection-' . $house->id . '.pdf';
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $path = "reports/{$filename}";

        // Always generate fresh PDF so edits appear immediately in the report
        $binary = $generator->renderBinary($house);
        $disk->put($path, $binary);

        $disposition = $request->query('inline') ? 'inline' : 'attachment';

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
        ]);
    }
}
