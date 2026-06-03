<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NoteCategory;
use App\Models\ReadyNote;
use App\Models\RecommendationTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadyNotesController extends Controller
{
    /**
     * API: Get all categories with their notes for the dropdown.
     */
    public function categoriesJson(): JsonResponse
    {
        $categories = NoteCategory::with(['readyNotes', 'recommendationTemplates'])
            ->orderBy('sort_order')
            ->get();

        return response()->json($categories);
    }

    /**
     * API: Get notes for a specific category.
     */
    public function notesByCategoryJson(NoteCategory $category): JsonResponse
    {
        return response()->json($category->readyNotes()->orderBy('sort_order')->get());
    }

    /**
     * API: Get recommendation templates for a specific category.
     */
    public function recommendationsByCategoryJson(NoteCategory $category): JsonResponse
    {
        return response()->json($category->recommendationTemplates()->orderBy('sort_order')->get());
    }

    /**
     * Management page: Show all categories, notes, and recommendations.
     */
    public function manage(): View
    {
        $categories = NoteCategory::with(['readyNotes', 'recommendationTemplates'])
            ->orderBy('sort_order')
            ->get();

        $openCategory = request()->integer('open') ?: null;

        return view('admin.ready-notes.manage', compact('categories', 'openCategory'));
    }

    private function redirectToReadyNotes(
        ?int $categoryId = null,
        ?string $fragment = null,
        ?string $status = null,
    ): RedirectResponse {
        $url = route('admin.ready-notes.manage', $categoryId ? ['open' => $categoryId] : []);
        if ($fragment) {
            $url .= '#' . $fragment;
        }

        $redirect = redirect($url);
        if ($status) {
            $redirect->with('status', $status);
        }

        return $redirect;
    }

    // ── Category CRUD ──────────────────────────────────────────────

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ]);

        $max = (int) NoteCategory::max('sort_order');
        NoteCategory::create([
            ...$data,
            'sort_order' => $max + 1,
        ]);

        return $this->redirectToReadyNotes(null, 'category-new', 'تمت إضافة التصنيف.');
    }

    public function updateCategory(Request $request, NoteCategory $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ]);

        $category->update($data);

        return $this->redirectToReadyNotes($category->id, 'category-' . $category->id, 'تم تحديث التصنيف.');
    }

    public function destroyCategory(NoteCategory $category): RedirectResponse
    {
        $category->delete();

        return $this->redirectToReadyNotes(null, null, 'تم حذف التصنيف وجميع ملاحظاته.');
    }

    // ── Note CRUD ──────────────────────────────────────────────

    public function storeNote(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'note_category_id' => ['required', 'exists:note_categories,id'],
            'text' => ['required', 'string', 'max:2000'],
        ]);

        $max = (int) ReadyNote::where('note_category_id', $data['note_category_id'])->max('sort_order');
        ReadyNote::create([
            ...$data,
            'sort_order' => $max + 1,
        ]);

        return $this->redirectToReadyNotes((int) $data['note_category_id'], 'category-' . $data['note_category_id'], 'تمت إضافة الملاحظة.');
    }

    public function updateNote(Request $request, ReadyNote $note): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'max:2000'],
        ]);

        $note->update($data);

        if ($request->boolean('ajax')) {
            return response()->json([
                'message' => 'تم تحديث الملاحظة.',
                'text' => $note->text,
                'id' => $note->id,
            ]);
        }

        return $this->redirectToReadyNotes($note->note_category_id, 'note-' . $note->id, 'تم تحديث الملاحظة.');
    }

    public function destroyNote(ReadyNote $note): RedirectResponse
    {
        $categoryId = $note->note_category_id;
        $note->delete();

        return $this->redirectToReadyNotes($categoryId, 'category-' . $categoryId, 'تم حذف الملاحظة.');
    }

    // ── Recommendation Template CRUD ──────────────────────────────────

    public function storeRecommendation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'note_category_id' => ['required', 'exists:note_categories,id'],
            'text' => ['required', 'string', 'max:2000'],
        ]);

        $max = (int) RecommendationTemplate::where('note_category_id', $data['note_category_id'])->max('sort_order');
        RecommendationTemplate::create([
            ...$data,
            'sort_order' => $max + 1,
        ]);

        return $this->redirectToReadyNotes((int) $data['note_category_id'], 'category-' . $data['note_category_id'], 'تمت إضافة التوصية.');
    }

    public function updateRecommendation(Request $request, RecommendationTemplate $recommendation): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'max:2000'],
        ]);

        $recommendation->update($data);

        if ($request->boolean('ajax')) {
            return response()->json([
                'message' => 'تم تحديث التوصية.',
                'text' => $recommendation->text,
                'id' => $recommendation->id,
            ]);
        }

        return $this->redirectToReadyNotes($recommendation->note_category_id, 'rec-' . $recommendation->id, 'تم تحديث التوصية.');
    }

    public function destroyRecommendation(RecommendationTemplate $recommendation): RedirectResponse
    {
        $categoryId = $recommendation->note_category_id;
        $recommendation->delete();

        return $this->redirectToReadyNotes($categoryId, 'category-' . $categoryId, 'تم حذف التوصية.');
    }
}
