@extends('layouts.admin')

@section('title', 'الأقسام الجاهزة')

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-black text-white tracking-tight">الأقسام الجاهزة</h1>
        <p class="mt-2 text-slate-400">تحكم في أسماء الأقسام التي تظهر عند إضافة قسم للمنزل، واربط كل قسم بتصنيف ملاحظات لتحميل الملاحظات والتوصيات تلقائياً.</p>
    </div>

    <div class="mb-8 rounded-2xl border border-slate-800 bg-slate-900/40 p-6">
        <h2 class="text-lg font-bold text-emerald-400 mb-4">إضافة قسم جديد</h2>
        <form method="post" action="{{ route('admin.ready-sections.store') }}" class="flex flex-wrap items-end gap-4">
            @csrf
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-slate-300 mb-1">اسم القسم</label>
                <input name="name" type="text" required class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-white focus:border-emerald-500 focus:outline-none" placeholder="مثال: الجدران">
            </div>
            <div class="flex-1 min-w-[220px]">
                <label class="block text-sm font-medium text-slate-300 mb-1">تصنيف الملاحظات (اختياري)</label>
                <select name="note_category_id" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-white focus:border-emerald-500 focus:outline-none">
                    <option value="">— بدون ربط —</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-xl bg-emerald-500 px-6 py-2.5 font-bold text-slate-950 hover:bg-emerald-400 transition whitespace-nowrap">+ إضافة</button>
        </form>
    </div>

    <div class="space-y-3">
        @forelse($sections as $section)
            <div id="section-{{ $section->id }}" data-ready-row class="rounded-2xl border border-slate-800 bg-slate-900/30 p-5 scroll-mt-24" x-data="{ editing: false }">
                <div x-show="!editing">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-4 flex-1 min-w-0">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-sm font-bold text-slate-300">{{ $section->sort_order }}</span>
                            <div class="min-w-0">
                                <p data-ready-field="name" class="text-lg font-bold text-white">{{ $section->name }}</p>
                                @if($section->noteCategory)
                                    <p data-ready-field="category" class="text-xs text-emerald-500/80 mt-0.5">مرتبط بتصنيف: {{ $section->noteCategory->name }}</p>
                                @else
                                    <p data-ready-field="category" class="text-xs text-slate-500 mt-0.5">غير مرتبط بتصنيف ملاحظات</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" @click="editing = true" class="rounded-lg border border-slate-600 px-4 py-2 text-xs font-bold text-slate-200 hover:bg-slate-800 transition">تعديل</button>
                            <form method="post" action="{{ route('admin.ready-sections.destroy', $section) }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" onclick="confirmAction('حذف هذا القسم من القائمة؟', () => this.closest('form').submit())" class="rounded-lg bg-red-500/10 px-4 py-2 text-xs font-bold text-red-400 hover:bg-red-500/20 transition">حذف</button>
                            </form>
                        </div>
                    </div>
                </div>
                <form x-show="editing" x-cloak method="post" action="{{ route('admin.ready-sections.update', $section) }}" data-ajax-ready-form class="flex flex-wrap items-end gap-3">
                    @csrf
                    @method('PATCH')
                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-xs text-slate-500 mb-1">اسم القسم</label>
                        <input name="name" value="{{ $section->name }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs text-slate-500 mb-1">تصنيف الملاحظات</label>
                        <select name="note_category_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none">
                            <option value="">— بدون ربط —</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected($section->note_category_id == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="rounded-lg bg-emerald-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-emerald-400">حفظ</button>
                    <button type="button" @click="editing = false" class="rounded-lg border border-slate-600 px-4 py-2 text-xs font-bold text-slate-300 hover:bg-slate-800">إلغاء</button>
                </form>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-700 p-10 text-center text-slate-500">
                لا توجد أقسام بعد. أضف قسماً من النموذج أعلاه.
            </div>
        @endforelse
    </div>
@endsection
