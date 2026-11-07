<div class="hp-table-card rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 divide-y divide-slate-200 dark:divide-slate-700 text-xs overflow-hidden mb-2.5">
    <!-- Action info -->
    <div class="hp-table-row flex items-center justify-between px-3 py-2">
        <span class="hp-label text-slate-500 dark:text-slate-400 font-medium">Method & Action</span>
        <span class="hp-value font-semibold text-slate-900 dark:text-white">
            bKash <span class="font-normal text-slate-500 dark:text-slate-400">· Send Money (*247# / App)</span>
        </span>
    </div>

    <!-- Recipient Number -->
    <div class="hp-table-row flex items-center justify-between px-3 py-2 gap-2">
        <span class="hp-label text-slate-500 dark:text-slate-400 font-medium shrink-0">bKash Number</span>
        <div class="flex items-center gap-1.5" x-data="{ copied: false }">
            <code class="hp-code font-mono font-bold text-slate-900 dark:text-white tracking-wider text-xs bg-white dark:bg-slate-900 px-1.5 py-0.5 rounded border border-slate-300 dark:border-slate-700">01624093099</code>
            <button type="button" @click="copyToClipboard('01624093099'); copied = true; setTimeout(() => copied = false, 2000)"
                :class="copied ? 'bg-emerald-700' : 'bg-emerald-600 hover:bg-emerald-500'"
                class="hp-copy-btn inline-flex items-center gap-1 px-2 py-1 rounded-md active:scale-95 text-white font-medium text-[11px] transition cursor-pointer">
                <template x-if="!copied">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </template>
                <template x-if="copied">
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </template>
                <span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
            </button>
        </div>
    </div>

    <!-- Amount -->
    <div class="hp-table-row flex items-center justify-between px-3 py-2 gap-2">
        <span class="hp-label text-slate-500 dark:text-slate-400 font-medium shrink-0">Amount</span>
        <div class="flex items-center gap-1.5" x-data="{ copied: false }">
            <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-xs">{{ number_format($invoice->amount, 2) }} BDT</span>
            <button type="button" @click="copyToClipboard('{{ $invoice->amount }}'); copied = true; setTimeout(() => copied = false, 2000)"
                :class="copied ? 'bg-emerald-700' : 'bg-emerald-600 hover:bg-emerald-500'"
                class="hp-copy-btn inline-flex items-center gap-1 px-2 py-1 rounded-md active:scale-95 text-white font-medium text-[11px] transition cursor-pointer">
                <template x-if="!copied">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </template>
                <template x-if="copied">
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </template>
                <span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
            </button>
        </div>
    </div>

    <!-- Reference (Optional) -->
    <div class="hp-table-row flex items-center justify-between px-3 py-2 gap-2">
        <span class="hp-label text-slate-500 dark:text-slate-400 font-medium shrink-0">Reference (Optional)</span>
        <div class="flex items-center gap-1.5" x-data="{ copied: false }">
            <code class="hp-code font-mono font-bold text-slate-900 dark:text-white tracking-wider text-xs bg-white dark:bg-slate-900 px-1.5 py-0.5 rounded border border-slate-300 dark:border-slate-700">{{ $invoice->reference }}</code>
            <button type="button" @click="copyToClipboard('{{ $invoice->reference }}'); copied = true; setTimeout(() => copied = false, 2000)"
                :class="copied ? 'bg-emerald-700' : 'bg-emerald-600 hover:bg-emerald-500'"
                class="hp-copy-btn inline-flex items-center gap-1 px-2 py-1 rounded-md active:scale-95 text-white font-medium text-[11px] transition cursor-pointer">
                <template x-if="!copied">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </template>
                <template x-if="copied">
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </template>
                <span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
            </button>
        </div>
    </div>
</div>

<div class="hp-note-box p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-[11px] text-emerald-900 dark:text-emerald-200 flex items-center gap-2 mb-2.5">
    <span class="hp-note-icon w-4 h-4 rounded-full bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-[10px] shrink-0">i</span>
    <span>Confirm transfer with PIN, then enter TrxID below (or attach screenshot).</span>
</div>
