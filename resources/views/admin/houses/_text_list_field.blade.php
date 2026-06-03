{{-- $itemsVar: Alpine array name, $label, $accent: emerald|amber, $inputPrefix: notes_list|recommendations_list --}}
<div>
    <label class="block text-sm font-medium {{ $accent === 'amber' ? 'text-amber-400' : 'text-emerald-400' }} mb-2">{{ $label }}</label>
    <div class="space-y-2 mb-2">
        <template x-for="(item, index) in {{ $itemsVar }}" :key="index">
            <div class="flex items-start gap-2 rounded-lg border border-slate-700/60 bg-slate-950/80 p-2">
                <span class="shrink-0 mt-2 flex h-6 w-6 items-center justify-center rounded bg-slate-800 text-xs font-bold text-slate-400" x-text="index + 1"></span>
                <input type="hidden" :name="'{{ $inputPrefix }}[' + index + ']'" :value="item">
                <textarea x-model="{{ $itemsVar }}[index]" rows="2" class="flex-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:outline-none {{ $accent === 'amber' ? 'focus:border-amber-500' : 'focus:border-emerald-500' }}" placeholder="اكتب النص..."></textarea>
                <button type="button" @click="{{ $itemsVar }}.splice(index, 1)" class="shrink-0 p-2 text-slate-500 hover:text-red-400 transition" title="حذف">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
            </div>
        </template>
        <p x-show="{{ $itemsVar }}.length === 0" class="text-xs text-slate-500 py-1">لا توجد عناصر بعد — أضف واحداً ثم احفظ.</p>
    </div>
    <button type="button" @click="{{ $itemsVar }}.push('')" class="rounded-lg border border-dashed {{ $accent === 'amber' ? 'border-amber-700/40 text-amber-400 hover:bg-amber-950/30' : 'border-emerald-700/40 text-emerald-400 hover:bg-emerald-950/30' }} px-4 py-2 text-xs font-bold transition">
        + إضافة {{ $label === 'التوصيات' ? 'توصية' : 'ملاحظة' }}
    </button>
</div>
