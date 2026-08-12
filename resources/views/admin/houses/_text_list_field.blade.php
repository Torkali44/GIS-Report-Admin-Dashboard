@php
    $textVar = $itemsVar . 'Text';
    $textInputName = ($inputPrefix === 'notes_list') ? 'notes_text' : 'recommendations_text';
@endphp
<div>
    <label class="block text-sm font-medium {{ $accent === 'amber' ? 'text-amber-400' : 'text-emerald-400' }} mb-1">{{ $label }}</label>
    <p class="text-xs text-slate-400 mb-2">أدخل جميع {{ $label === 'توصيات إضافية (يدوي)' ? 'التوصيات' : 'الملاحظات' }} هنا دفعة واحدة — كلاً في سطر جديد بدون ترقيم:</p>
    
    <textarea
        name="{{ $textInputName }}"
        x-model="{{ $textVar }}"
        @input="syncListsFromText()"
        rows="4"
        class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white leading-relaxed focus:outline-none {{ $accent === 'amber' ? 'focus:border-amber-500' : 'focus:border-emerald-500' }}"
        placeholder="اكتب أو الصق البنود هنا... كلاً في سطر جديد تلقائياً (مثل:&#10;تركيب شبك حماية لمروحة الشفط&#10;تركيب عازل الغبار لباب الألمنيوم)"></textarea>

    <template x-for="(item, index) in {{ $itemsVar }}" :key="index">
        <input type="hidden" :name="'{{ $inputPrefix }}[' + index + ']'" :value="item">
    </template>
</div>
