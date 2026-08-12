{{-- النتيجة النهائية للتقرير PDF --}}
<div class="rounded-2xl border border-amber-700/40 bg-amber-950/20 p-6">
    <h2 class="text-lg font-bold text-amber-400 mb-1">النتيجة النهائية للتقرير</h2>
    <p class="text-sm text-slate-400 mb-4">اكتب نص النتيجة يدوياً. النسبة والتقييم في الأسفل تُؤخذ تلقائياً من «النسبة الإجمالية» ({{ $house->total_percentage }}% — {{ \App\Support\InspectionScoreLabels::label((int) $house->total_percentage) }}).</p>

    <form method="post" action="{{ route('admin.houses.final-result.update', $house) }}" class="space-y-4">
        @csrf
        @method('PATCH')

        <div>
            <label for="final_result_text" class="block text-sm font-medium text-slate-300 mb-2">نص النتيجة النهائية</label>
            <textarea id="final_result_text" name="final_result_text" rows="12"
                class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white leading-relaxed focus:border-amber-500 focus:outline-none"
                placeholder="اكتب فقرات النتيجة والملاحظات الرئيسية...">{{ old('final_result_text', $house->final_result_text) }}</textarea>
        </div>

        <div>
            <label for="final_general_notes" class="block text-sm font-medium text-slate-300 mb-2">ملاحظات عامة (اختياري)</label>
            <textarea id="final_general_notes" name="final_general_notes" rows="4"
                class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white leading-relaxed focus:border-amber-500 focus:outline-none"
                placeholder="ملاحظات عامة تظهر قبل تقييم الفاحص...">{{ old('final_general_notes', $house->final_general_notes) }}</textarea>
        </div>

        {{-- تجاوز تقييم الفاحص --}}
        <div class="max-w-xs">
            <label for="inspector_rating_override" class="block text-sm font-medium text-slate-300 mb-1">تقييم الفاحص للعقار</label>
            <p class="text-xs text-slate-500 mb-2">
                التقييم التلقائي الحالي:
                <span class="font-bold text-amber-400">{{ \App\Support\InspectionScoreLabels::label((int) $house->total_percentage) }}</span>
                — اتركه على «تلقائي» إذا كان مناسباً، أو اختر تقييماً يدوياً.
            </p>
            <select id="inspector_rating_override" name="inspector_rating_override"
                class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white focus:border-amber-500 focus:outline-none">
                <option value="" {{ old('inspector_rating_override', $house->inspector_rating_override) === null || old('inspector_rating_override', $house->inspector_rating_override) === '' ? 'selected' : '' }}>
                    ← تلقائي ({{ \App\Support\InspectionScoreLabels::label((int) $house->total_percentage) }})
                </option>
                @foreach(['ممتاز', 'جيد جداً', 'جيد', 'متوسط', 'ضعيف'] as $ratingOption)
                    <option value="{{ $ratingOption }}"
                        {{ old('inspector_rating_override', $house->inspector_rating_override) === $ratingOption ? 'selected' : '' }}>
                        {{ $ratingOption }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="max-w-xs">
            <label for="report_delivered_at" class="block text-sm font-medium text-slate-300 mb-2">تاريخ تسليم التقرير</label>
            <input id="report_delivered_at" name="report_delivered_at" type="date"
                value="{{ old('report_delivered_at', optional($house->report_delivered_at)->format('Y-m-d')) }}"
                class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white focus:border-amber-500 focus:outline-none">
        </div>

        <button type="submit" class="rounded-xl bg-amber-500 px-6 py-2.5 text-sm font-bold text-slate-950 hover:bg-amber-400 transition">
            حفظ النتيجة النهائية
        </button>
    </form>
</div>
