<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NoteCategory;
use App\Models\ReadySection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadySectionsController extends Controller
{
    public function manage(): View
    {
        $sections = ReadySection::with('noteCategory')
            ->orderBy('sort_order')
            ->get();

        $categories = NoteCategory::orderBy('sort_order')->get();

        return view('admin.ready-sections.manage', compact('sections', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'note_category_id' => ['nullable', 'exists:note_categories,id'],
        ]);

        $max = (int) ReadySection::max('sort_order');
        ReadySection::create([
            ...$data,
            'note_category_id' => $data['note_category_id'] ?? null,
            'sort_order' => $max + 1,
        ]);

        return back()->with('status', 'تمت إضافة القسم.');
    }

    public function update(Request $request, ReadySection $section): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'note_category_id' => ['nullable', 'exists:note_categories,id'],
        ]);

        $section->update([
            'name' => $data['name'],
            'note_category_id' => $data['note_category_id'] ?? null,
        ]);

        if ($request->boolean('ajax')) {
            $section->load('noteCategory');

            return response()->json([
                'message' => 'تم تحديث القسم.',
                'name' => $section->name,
                'category_name' => $section->noteCategory?->name,
                'id' => $section->id,
            ]);
        }

        return redirect()
            ->route('admin.ready-sections.manage')
            ->withFragment('section-' . $section->id)
            ->with('status', 'تم تحديث القسم.');
    }

    public function destroy(ReadySection $section): RedirectResponse
    {
        $section->delete();

        return back()->with('status', 'تم حذف القسم.');
    }
}
