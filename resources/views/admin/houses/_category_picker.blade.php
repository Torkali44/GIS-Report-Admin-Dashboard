{{-- Requires Alpine: selectedCategory, loadCategoryData, categoryNotes, categoryRecs, toggleNote, toggleRec, isNoteSelected, isRecSelected --}}
<div>
    <label class="block text-sm font-medium text-emerald-400 mb-2">تصنيف الملاحظات</label>
    <select x-model="selectedCategory" @change="onCategoryChange()" class="w-full rounded-xl border border-emerald-700/50 bg-slate-950 px-4 py-3 text-white focus:border-emerald-500 focus:outline-none text-sm">
        <option value="">-- اختر التصنيف --</option>
        @foreach($categories->unique('name') as $cat)
            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
        @endforeach
    </select>
</div>

<div x-show="selectedCategory" x-cloak class="space-y-2">
    <label class="block text-sm font-medium text-emerald-400 mb-1">اختر الملاحظات الجاهزة</label>
    <p x-show="categoryNotes.length === 0" class="text-xs text-slate-500 rounded-lg border border-dashed border-slate-700 p-3">لا توجد ملاحظات في هذا التصنيف.</p>
    <div x-show="categoryNotes.length > 0" class="max-h-48 overflow-y-auto rounded-xl border border-slate-700 bg-slate-950 p-3 space-y-2 custom-scrollbar">
        <template x-for="note in categoryNotes" :key="note.id">
            <label class="flex items-start gap-2 p-2 rounded-lg hover:bg-slate-800/50 cursor-pointer transition group">
                <input type="checkbox" :checked="isNoteSelected(note)" @change="toggleNote(note)" class="mt-1 shrink-0 rounded border-slate-600 text-emerald-500 focus:ring-emerald-500 bg-slate-800">
                <span class="text-xs text-slate-400 group-hover:text-slate-200 leading-relaxed" x-text="note.text"></span>
            </label>
        </template>
    </div>
</div>

<div class="pt-1">
    <label class="block text-sm font-medium text-amber-400 mb-2">التوصيات الجاهزة</label>
    <div x-show="selectedCategory && categoryRecs.length > 0" x-cloak class="mb-2">
        <div class="max-h-40 overflow-y-auto rounded-xl border border-amber-700/30 bg-slate-950 p-3 space-y-2 custom-scrollbar">
            <template x-for="rec in categoryRecs" :key="rec.id">
                <label class="flex items-start gap-2 p-2 rounded-lg hover:bg-amber-950/30 cursor-pointer transition group">
                    <input type="checkbox" :checked="isRecSelected(rec)" @change="toggleRec(rec)" class="mt-1 shrink-0 rounded border-amber-700/50 text-amber-500 focus:ring-amber-500 bg-slate-800">
                    <span class="text-xs text-amber-200/60 group-hover:text-amber-200 leading-relaxed" x-text="rec.text"></span>
                </label>
            </template>
        </div>
    </div>
    <p x-show="selectedCategory && categoryRecs.length === 0" class="text-xs text-slate-500 mb-2">لا توجد توصيات في هذا التصنيف.</p>
</div>
