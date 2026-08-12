@extends('layouts.admin')

@section('title', $house->title)

@section('content')
    <div data-house-page>
    <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3 rtl:space-x-reverse">
                    <li class="inline-flex items-center">
                        <a href="{{ route('admin.houses.index') }}"
                            class="text-sm font-medium text-slate-400 hover:text-emerald-400 transition-colors">
                            <svg class="w-4 h-4 mr-2.5 rtl:ml-2.5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z">
                                </path>
                            </svg>
                            جميع المنازل
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center"><svg class="w-6 h-6 text-slate-600" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg><span class="ms-1 text-sm font-medium text-emerald-500 md:ms-2">تفاصيل العقار
                                والتقارير</span></div>
                    </li>
                </ol>
            </nav>
            <h1 class="text-3xl font-black text-white tracking-tight">{{ $house->title }}</h1>
            <div class="mt-2 flex items-center gap-3">
                <span class="text-sm font-bold text-slate-500 uppercase tracking-wider">النسبة الإجمالية:</span>
                @php
                    $totalColor = 'text-slate-400';
                    $totalLabel = \App\Support\InspectionScoreLabels::label((int) $house->total_percentage);
                    if ($house->total_percentage >= 80)
                        $totalColor = 'text-emerald-500';
                    elseif ($house->total_percentage >= 60)
                        $totalColor = 'text-blue-500';
                    else
                        $totalColor = 'text-amber-500';
                @endphp
                <span class="text-2xl font-black {{ $totalColor }}">{{ $house->total_percentage }}%</span>
                <span class="text-sm font-bold text-slate-400">({{ $totalLabel }})</span>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.houses.edit', $house) }}"
                class="flex items-center gap-2 rounded-xl border border-slate-700 px-4 py-3 font-bold text-slate-300 hover:bg-slate-800">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                <span>تعديل البيانات</span>
            </a>
            <a href="{{ route('admin.houses.report', ['house' => $house, 'inline' => 1]) }}" target="_blank"
                class="group flex items-center gap-2 rounded-xl bg-emerald-500/20 px-4 py-3 font-bold text-emerald-400 transition hover:bg-emerald-500 hover:text-slate-950">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <span>عرض التقرير</span>
            </a>
            <a href="{{ route('admin.houses.report', $house) }}"
                class="group flex items-center gap-2 rounded-xl bg-emerald-500 px-6 py-3 font-bold text-slate-950 transition hover:bg-emerald-400 hover:shadow-[0_0_20px_rgba(16,185,129,0.2)]">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <span>تحميل PDF</span>
            </a>
            <a href="{{ route('admin.houses.report.word', $house) }}"
                class="group flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 font-bold text-white transition hover:bg-blue-500 hover:shadow-[0_0_20px_rgba(59,130,246,0.3)]">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>تحميل Word</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Sidebar: Add Area with Dropdowns -->
        <div class="lg:col-span-4">
            <div class="sticky top-8 rounded-2xl border border-slate-800 bg-slate-900/40 p-6" x-data="areaForm()">
                <h2 class="text-xl font-bold text-white mb-4">إضافة قسم جديد</h2>
                <p class="text-sm text-slate-400 mb-6">اختر القسم والتصنيف من القوائم الجاهزة أو أدخل بيانات مخصصة.</p>

                <form method="post" action="{{ route('admin.houses.areas.store', $house) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">اسم القسم</label>
                        <select name="name" x-model="selectedSection" @change="onSectionChange()" required
                            class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white focus:border-emerald-500 focus:outline-none">
                            <option value="">-- اختر القسم --</option>
                            @foreach($readySections->unique('name') as $sec)
                                <option value="{{ $sec->name }}">{{ $sec->name }}</option>
                            @endforeach
                            <option value="__custom__">أخرى (إدخال يدوي)</option>
                        </select>
                        <input x-show="selectedSection === '__custom__' || selectedSection === ''" x-model="customSection" @input="onCustomSectionInput()" type="text"
                            name="custom_section_name"
                            x-bind:disabled="selectedSection !== '__custom__' && selectedSection !== ''"
                            :required="selectedSection === '__custom__' || selectedSection === ''"
                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white focus:border-emerald-500 focus:outline-none"
                            placeholder="أدخل اسم القسم">
                    </div>

                    {{-- Score --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">النسبة (0 - 100)</label>
                        <input name="score" type="number" min="0" max="100" value="0" required
                            class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white focus:border-emerald-500 focus:outline-none">
                    </div>

                    @include('admin.houses._category_picker')
                        @include('admin.houses._text_list_field', ['itemsVar' => 'notesList', 'label' => 'ملاحظات إضافية (يدوي)', 'accent' => 'emerald', 'inputPrefix' => 'notes_list'])
                        @include('admin.houses._text_list_field', ['itemsVar' => 'recommendationsList', 'label' => 'توصيات إضافية (يدوي)', 'accent' => 'amber', 'inputPrefix' => 'recommendations_list'])

                        <button type="submit" class="w-full rounded-xl bg-emerald-500 py-3 font-bold text-slate-950 transition hover:bg-emerald-400">
                            إضافة القسم للتقرير
                        </button>
                    </form>
                </div>
        </div>

        <!-- Main Content: Areas -->
        <div class="lg:col-span-8 space-y-4">
                <h2 class="text-xl font-bold text-white mb-2">النسب الفرعية والأقسام</h2>
                @forelse ($house->inspectionAreas as $area)
                    @php
                        $colorClass = 'bg-slate-700';
                        $label = 'ضعيف';
                        if ($area->score >= 80) {
                            $colorClass = 'bg-green-500';
                            $label = 'ممتاز';
                        } elseif ($area->score >= 70) {
                            $colorClass = 'bg-yellow-400';
                            $label = 'جيد';
                        } elseif ($area->score >= 60) {
                            $colorClass = 'bg-[#8b4513]';
                            $label = 'متوسط';
                        } else {
                            $colorClass = 'bg-[#F70000]';
                            $label = 'ضعيف';
                        }
                    @endphp
                    <div id="area-{{ $area->id }}" class="rounded-2xl border border-slate-800 bg-slate-900/20 overflow-hidden"
                         x-data="areaCardEditor(@js($area->notesList()), @js($area->recommendationsList()), @js($area->name))">
                        <div class="flex items-center justify-between bg-slate-900/40 px-6 py-4 border-b border-slate-800">
                            <div class="flex items-center gap-4 flex-1 min-w-0">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-500 font-bold text-sm">{{ $area->sort_order }}</span>
                                <div class="flex-1 min-w-0" x-show="!editing">
                                    <h3 data-area-name class="text-lg font-bold text-white">{{ $area->name }}</h3>
                                    <div class="flex items-center gap-3 mt-1">
                                        <div class="h-2 w-32 bg-slate-800 rounded-full overflow-hidden"><div data-area-score-bar class="h-full {{ $colorClass }}" style="width: {{ $area->score }}%"></div></div>
                                        <span data-area-score-text class="text-xs font-bold text-slate-400">{{ $area->score }}% ({{ $label }})</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-2" x-show="!editing">
                                <button type="button" @click="startEditing()" class="rounded-lg border border-emerald-600/50 bg-emerald-500/10 px-3 py-1.5 text-xs font-bold text-emerald-400 hover:bg-emerald-500/20 transition">تعديل</button>
                                <form method="post" action="{{ route('admin.houses.areas.reorder', [$house, $area]) }}" data-ajax-reorder class="flex items-center gap-1">
                                    @csrf @method('PATCH')
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">الترتيب</span>
                                    <input type="number" name="sort_order" value="{{ $area->sort_order }}" class="w-12 h-8 rounded-lg border border-slate-700 bg-slate-950 text-center text-xs text-white focus:border-emerald-500 focus:outline-none transition-colors hover:border-slate-600">
                                </form>
                                <form id="delete-area-form-{{ $area->id }}" method="post" action="{{ route('admin.houses.areas.destroy', [$house, $area]) }}">
                                    @csrf @method('DELETE')
                                    <button type="button" onclick="confirmAction('حذف هذا القسم؟', function() { document.getElementById('delete-area-form-{{ $area->id }}').submit(); })" class="p-2 text-slate-500 hover:text-red-400 transition-colors" title="حذف">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <form x-show="editing" x-cloak method="post" action="{{ route('admin.houses.areas.update', [$house, $area]) }}" data-ajax-area-form class="px-6 py-4 space-y-4 border-b border-slate-800 bg-slate-900/30">
                            @csrf @method('PATCH')
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-slate-500 mb-1">اسم القسم</label>
                                    <input type="text" name="name" x-model="areaNameField" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-500 mb-1">النسبة</label>
                                    <input type="number" name="score" value="{{ $area->score }}" min="0" max="100" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none">
                                </div>
                            </div>
                            <div class="rounded-xl border border-emerald-700/30 bg-slate-950/50 p-4 space-y-3">
                                <p class="text-xs font-bold text-emerald-400">اختر من الجاهز أو أضف يدوياً</p>
                                @include('admin.houses._category_picker')
                            </div>
                            @include('admin.houses._text_list_field', ['itemsVar' => 'notesList', 'label' => 'ملاحظات إضافية (يدوي)', 'accent' => 'emerald', 'inputPrefix' => 'notes_list'])
                            @include('admin.houses._text_list_field', ['itemsVar' => 'recommendationsList', 'label' => 'توصيات إضافية (يدوي)', 'accent' => 'amber', 'inputPrefix' => 'recommendations_list'])
                            <div class="flex gap-2">
                                <button type="submit" class="rounded-lg bg-emerald-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-emerald-400">حفظ التعديلات</button>
                                <button type="button" @click="editing = false" class="rounded-lg bg-slate-800 px-4 py-2 text-xs font-bold text-white hover:bg-slate-700">إلغاء</button>
                            </div>
                        </form>

                        <div class="px-6 py-4 bg-slate-900/10 border-t border-slate-800/50" x-show="!editing">
                            <p class="text-xs font-bold text-emerald-500 uppercase tracking-wider mb-2">الملاحظات الفنية:</p>
                            <div data-area-notes>
                                @if(count($area->notesList()) > 0)
                                    <ol class="list-decimal list-inside space-y-2 text-sm text-slate-300 leading-relaxed">
                                        @foreach($area->notesList() as $noteLine)
                                            <li class="pr-1">{{ $noteLine }}</li>
                                        @endforeach
                                    </ol>
                                @else
                                    <p class="text-sm text-slate-500">لا توجد ملاحظات — اضغط «تعديل» لإضافتها.</p>
                                @endif
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-amber-950/10 border-t border-slate-800" x-show="!editing">
                            <p class="text-xs font-bold text-amber-500 uppercase tracking-wider mb-2">التوصيات:</p>
                            <div data-area-recs>
                                @if(count($area->recommendationsList()) > 0)
                                    <ol class="list-decimal list-inside space-y-2 text-sm text-amber-200/80 leading-relaxed">
                                        @foreach($area->recommendationsList() as $recLine)
                                            <li class="pr-1">{{ $recLine }}</li>
                                        @endforeach
                                    </ol>
                                @else
                                    <p class="text-sm text-slate-500">لا توجد توصيات — اضغط «تعديل» لإضافتها.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-800 p-16 text-center">
                        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-slate-900/50 text-slate-600">
                            <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </div>
                        <h3 class="text-xl font-bold text-white">لا توجد أقسام في التقرير بعد</h3>
                        <p class="mt-2 text-slate-500">ابدأ بإضافة الأقسام والنسب المئوية من القائمة الجانبية.</p>
                    </div>
                @endforelse

            @include('admin.houses._final_result_form')
        </div>
    </div>

        {{-- Data for Alpine --}}
    <script type="application/json" id="categories-json-data">{!! \Illuminate\Support\Js::encode($categories) !!}</script>
    <script type="application/json" id="ready-sections-json-data">{!! \Illuminate\Support\Js::encode($readySections) !!}</script>
    </div>
@endsection

@push('styles')
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(15,23,42,0.5); border-radius: 8px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(16,185,129,0.3); border-radius: 8px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(16,185,129,0.5); }
        [x-cloak] { display: none !important; }
        [id^="area-"] { scroll-margin-top: 6rem; }
    </style>
@endpush
