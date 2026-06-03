@extends('layouts.admin')

@section('title', 'إدارة الملاحظات الجاهزة')

@section('content')
    <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-black text-white tracking-tight">إدارة الملاحظات الجاهزة والتوصيات</h1>
            <p class="mt-2 text-slate-400">أضف وعدل وأحذف التصنيفات والملاحظات الجاهزة والتوصيات التي تظهر عند إضافة الأقسام.</p>
        </div>
    </div>

    {{-- Add New Category --}}
    <div class="mb-8 rounded-2xl border border-slate-800 bg-slate-900/40 p-6">
        <h2 class="text-lg font-bold text-emerald-400 mb-4">إضافة تصنيف جديد</h2>
        <form method="post" action="{{ route('admin.ready-notes.categories.store') }}" class="flex items-end gap-4 flex-wrap">
            @csrf
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-slate-300 mb-1">اسم التصنيف (عربي)</label>
                <input name="name" type="text" required class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-white focus:border-emerald-500 focus:outline-none" placeholder="مثال: الجدران">
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-slate-300 mb-1">اسم التصنيف (إنجليزي)</label>
                <input name="name_en" type="text" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-white focus:border-emerald-500 focus:outline-none" placeholder="e.g. Walls">
            </div>
            <button type="submit" class="rounded-xl bg-emerald-500 px-6 py-2.5 font-bold text-slate-950 hover:bg-emerald-400 transition whitespace-nowrap">
                + إضافة تصنيف
            </button>
        </form>
    </div>

    {{-- Categories Accordion --}}
    <div class="space-y-4" x-data="{ openCategory: {{ $openCategory ?? 'null' }} }">
        @foreach($categories as $category)
            <div id="category-{{ $category->id }}" class="rounded-2xl border border-slate-800 bg-slate-900/20 overflow-hidden scroll-mt-24">
                {{-- Category Header --}}
                <div class="flex items-center justify-between bg-slate-900/40 px-6 py-4 border-b border-slate-800 cursor-pointer"
                     @click="openCategory = openCategory === {{ $category->id }} ? null : {{ $category->id }}">
                    <div class="flex items-center gap-4">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-500 font-bold text-sm">{{ $category->sort_order }}</span>
                        <div>
                            <h3 class="text-lg font-bold text-white">{{ $category->name }}</h3>
                            @if($category->name_en)
                                <span class="text-xs text-slate-500">{{ $category->name_en }}</span>
                            @endif
                        </div>
                        <span class="rounded-full bg-blue-500/20 px-3 py-1 text-xs font-bold text-blue-400">{{ $category->readyNotes->count() }} ملاحظة</span>
                        <span class="rounded-full bg-amber-500/20 px-3 py-1 text-xs font-bold text-amber-400">{{ $category->recommendationTemplates->count() }} توصية</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-slate-400 transition-transform" :class="{ 'rotate-180': openCategory === {{ $category->id }} }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

                {{-- Category Body --}}
                <div x-show="openCategory === {{ $category->id }}" x-cloak class="px-6 py-4 space-y-6">
                    {{-- Edit Category --}}
                    <div class="flex items-end gap-3 flex-wrap p-4 rounded-xl bg-slate-800/30 border border-slate-700/50">
                        <form method="post" action="{{ route('admin.ready-notes.categories.update', $category) }}" class="flex items-end gap-3 flex-1 flex-wrap">
                            @csrf
                            @method('PATCH')
                            <div class="flex-1 min-w-[150px]">
                                <label class="block text-xs text-slate-500 mb-1">اسم التصنيف</label>
                                <input name="name" value="{{ $category->name }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none">
                            </div>
                            <div class="flex-1 min-w-[150px]">
                                <label class="block text-xs text-slate-500 mb-1">الاسم بالإنجليزي</label>
                                <input name="name_en" value="{{ $category->name_en }}" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none">
                            </div>
                            <button type="submit" class="rounded-lg bg-emerald-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-emerald-400">حفظ</button>
                        </form>
                        <form method="post" action="{{ route('admin.ready-notes.categories.destroy', $category) }}">
                            @csrf
                            @method('DELETE')
                            <button type="button" onclick="confirmAction('حذف التصنيف وجميع ملاحظاته؟', () => this.closest('form').submit())" class="rounded-lg bg-red-500/20 px-4 py-2 text-xs font-bold text-red-400 hover:bg-red-500 hover:text-white transition">حذف التصنيف</button>
                        </form>
                    </div>

                    {{-- Notes Section --}}
                    <div>
                        <h4 class="text-sm font-bold text-emerald-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            الملاحظات الجاهزة
                        </h4>
                        <div class="space-y-2">
                            @foreach($category->readyNotes as $note)
                                <div id="note-{{ $note->id }}" data-ready-row class="flex items-start gap-3 rounded-xl bg-slate-800/20 border border-slate-700/30 p-3 scroll-mt-24" x-data="{ editingNote: false }">
                                    <span class="shrink-0 flex h-6 w-6 items-center justify-center rounded bg-slate-700 text-xs text-white font-bold mt-1">{{ $loop->iteration }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p x-show="!editingNote" data-ready-text class="text-sm text-slate-300 leading-relaxed">{{ $note->text }}</p>
                                        <form x-show="editingNote" x-cloak method="post" action="{{ route('admin.ready-notes.notes.update', $note) }}" data-ajax-ready-form class="flex items-end gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <textarea name="text" rows="2" class="flex-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none">{{ $note->text }}</textarea>
                                            <button type="submit" class="rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-bold text-slate-950 shrink-0">حفظ</button>
                                            <button type="button" @click="editingNote = false" class="rounded-lg border border-slate-600 px-3 py-1.5 text-xs font-bold text-slate-300 shrink-0">إلغاء</button>
                                        </form>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-1" x-show="!editingNote">
                                        <button type="button" @click="editingNote = true" class="rounded-lg border border-slate-600 px-3 py-1.5 text-xs font-bold text-slate-300 hover:bg-slate-800 transition">تعديل</button>
                                        <form method="post" action="{{ route('admin.ready-notes.notes.destroy', $note) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" onclick="confirmAction('حذف هذه الملاحظة؟', () => this.closest('form').submit())" class="p-1.5 text-slate-600 hover:text-red-400 transition" title="حذف">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Add New Note --}}
                        <form method="post" action="{{ route('admin.ready-notes.notes.store') }}" class="mt-3 flex items-end gap-3">
                            @csrf
                            <input type="hidden" name="note_category_id" value="{{ $category->id }}">
                            <div class="flex-1">
                                <textarea name="text" rows="2" required class="w-full rounded-lg border border-dashed border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none" placeholder="أضف ملاحظة جديدة... استخدم (الموقع) كمتغير"></textarea>
                            </div>
                            <button type="submit" class="rounded-lg bg-emerald-500/20 px-4 py-2 text-xs font-bold text-emerald-400 hover:bg-emerald-500 hover:text-slate-950 transition shrink-0">+ إضافة ملاحظة</button>
                        </form>
                    </div>

                    {{-- Recommendations Section --}}
                    <div>
                        <h4 class="text-sm font-bold text-amber-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" /></svg>
                            التوصيات الجاهزة
                        </h4>
                        <div class="space-y-2">
                            @foreach($category->recommendationTemplates as $rec)
                                <div id="rec-{{ $rec->id }}" data-ready-row class="flex items-start gap-3 rounded-xl bg-amber-950/10 border border-amber-700/20 p-3 scroll-mt-24" x-data="{ editingRec: false }">
                                    <span class="shrink-0 flex h-6 w-6 items-center justify-center rounded bg-amber-700/30 text-xs text-amber-400 font-bold mt-1">{{ $loop->iteration }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p x-show="!editingRec" data-ready-text class="text-sm text-amber-200/80 leading-relaxed">{{ $rec->text }}</p>
                                        <form x-show="editingRec" x-cloak method="post" action="{{ route('admin.ready-notes.recommendations.update', $rec) }}" data-ajax-ready-form class="flex items-end gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <textarea name="text" rows="2" class="flex-1 rounded-lg border border-amber-700/50 bg-slate-950 px-3 py-2 text-sm text-white focus:border-amber-500 focus:outline-none">{{ $rec->text }}</textarea>
                                            <button type="submit" class="rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-bold text-slate-950 shrink-0">حفظ</button>
                                            <button type="button" @click="editingRec = false" class="rounded-lg border border-slate-600 px-3 py-1.5 text-xs font-bold text-slate-300 shrink-0">إلغاء</button>
                                        </form>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-1" x-show="!editingRec">
                                        <button type="button" @click="editingRec = true" class="rounded-lg border border-amber-700/50 px-3 py-1.5 text-xs font-bold text-amber-300 hover:bg-amber-950/40 transition">تعديل</button>
                                        <form method="post" action="{{ route('admin.ready-notes.recommendations.destroy', $rec) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" onclick="confirmAction('حذف هذه التوصية؟', () => this.closest('form').submit())" class="p-1.5 text-slate-600 hover:text-red-400 transition" title="حذف">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <form method="post" action="{{ route('admin.ready-notes.recommendations.store') }}" class="mt-3 flex items-end gap-3">
                            @csrf
                            <input type="hidden" name="note_category_id" value="{{ $category->id }}">
                            <div class="flex-1">
                                <textarea name="text" rows="2" required class="w-full rounded-lg border border-dashed border-amber-700/30 bg-slate-950 px-3 py-2 text-sm text-white focus:border-amber-500 focus:outline-none" placeholder="أضف توصية جديدة..."></textarea>
                            </div>
                            <button type="submit" class="rounded-lg bg-amber-500/20 px-4 py-2 text-xs font-bold text-amber-400 hover:bg-amber-500 hover:text-slate-950 transition shrink-0">+ إضافة توصية</button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
