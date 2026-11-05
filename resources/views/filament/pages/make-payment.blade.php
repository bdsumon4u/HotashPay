@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        .fi-simple-main {
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: radial-gradient(circle at 50% 10%, #f1f5f9 0%, #e2e8f0 100%);
        }
        .dark .fi-simple-main {
            background: radial-gradient(circle at 50% 10%, #0f172a 0%, #020617 100%);
        }

        /* Scoped HotashPay modern compact styles */
        .hp-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
        }
        .dark .hp-card {
            background: #0f172a;
            border-color: #1e293b;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
        }

        .hp-table-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            overflow: hidden;
            margin-bottom: 0.625rem;
        }
        .dark .hp-table-card {
            background-color: rgba(30, 41, 59, 0.55);
            border-color: #334155;
        }

        .hp-table-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.45rem 0.75rem;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.75rem;
            line-height: 1.25rem;
            gap: 0.5rem;
        }
        .dark .hp-table-row {
            border-bottom-color: #334155;
        }
        .hp-table-row:last-child {
            border-bottom: none;
        }

        .hp-label {
            color: #64748b;
            font-weight: 500;
            flex-shrink: 0;
            font-size: 0.75rem;
        }
        .dark .hp-label {
            color: #94a3b8;
        }

        .hp-value {
            color: #0f172a;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.75rem;
            text-align: right;
        }
        .dark .hp-value {
            color: #f8fafc;
        }

        .hp-code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-weight: 700;
            letter-spacing: 0.025em;
            padding: 0.1rem 0.375rem;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0.35rem;
            color: #0f172a;
            font-size: 0.75rem;
        }
        .dark .hp-code {
            background-color: #0f172a;
            border-color: #334155;
            color: #f8fafc;
        }

        .hp-copy-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.2rem 0.5rem;
            background-color: #059669;
            color: #ffffff !important;
            border-radius: 0.375rem;
            font-size: 0.6875rem; /* 11px */
            font-weight: 600;
            line-height: 1;
            cursor: pointer;
            border: none;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transition: all 0.15s ease;
            user-select: none;
            text-decoration: none;
            flex-shrink: 0;
        }
        .hp-copy-btn:hover {
            background-color: #10b981;
        }
        .hp-copy-btn:active {
            transform: scale(0.95);
        }

        .hp-note-box {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 0.65rem;
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 0.5rem;
            font-size: 0.6875rem; /* 11px */
            color: #065f46;
            margin-bottom: 0.625rem;
            line-height: 1.25;
        }
        .dark .hp-note-box {
            background-color: rgba(6, 78, 59, 0.35);
            border-color: rgba(16, 185, 129, 0.35);
            color: #a7f3d0;
        }

        .hp-note-icon {
            width: 1rem;
            height: 1rem;
            border-radius: 9999px;
            background-color: rgba(16, 185, 129, 0.2);
            color: #059669;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.625rem;
            flex-shrink: 0;
        }
        .dark .hp-note-icon {
            color: #34d399;
        }

        .hp-dropzone {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            padding: 0.6rem 0.75rem;
            border: 1.5px dashed #cbd5e1;
            border-radius: 0.75rem;
            background-color: #f8fafc;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .dark .hp-dropzone {
            background-color: rgba(30, 41, 59, 0.4);
            border-color: #475569;
        }
        .hp-dropzone:hover {
            border-color: #10b981;
            background-color: #f0fdf4;
        }
        .dark .hp-dropzone:hover {
            border-color: #10b981;
            background-color: rgba(6, 78, 59, 0.2);
        }

        .hp-action-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.6rem 1rem;
            background: linear-gradient(135deg, #059669 0%, #0d9488 100%);
            color: #ffffff !important;
            font-weight: 600;
            font-size: 0.8125rem;
            border-radius: 0.75rem;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
            transition: all 0.15s ease;
        }
        .hp-action-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #10b981 0%, #14b8a6 100%);
            box-shadow: 0 6px 16px rgba(5, 150, 105, 0.35);
        }
        .hp-action-btn:active:not(:disabled) {
            transform: scale(0.99);
        }
        .hp-action-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
@endpush

<div class="w-full flex items-center justify-center p-3 sm:p-4"
    @if ($isVerifying) wire:poll.3s="checkVerificationStatus" @endif
    x-data="{
    currentView: @js($selectedProvider ? 'details' : 'methods'),
    activeTab: @js($selectedProvider ? (collect($gateways)->first(fn($m) => collect($m['drivers'])->pluck('folder')->contains($selectedProvider)) ? collect($gateways)->keys()->first(fn($k) => collect($gateways[$k]['drivers'] ?? [])->pluck('folder')->contains($selectedProvider)) : 'mobile') : 'mobile'),
    selectedProvider: @js($selectedProvider),
    showCopyToast: false,
    copyToastText: 'Copied to clipboard!',
    activeModal: null,
    showMfsUpload: false,
    providerTypeMap: {{ Illuminate\Support\Js::from(collect($gateways)->flatMap(fn($method, $type) => collect($method['drivers'])->mapWithKeys(fn($d) => [$d['folder'] => $type]))->toArray()) }},
    bankDrivers: {{ Illuminate\Support\Js::from(collect($gateways['ibanking']['drivers'] ?? [])->pluck('folder')->toArray()) }},
    get isBank() {
        if (!this.selectedProvider) return false;
        return this.bankDrivers.includes(this.selectedProvider) || this.selectedProvider.startsWith('bank-');
    },
    chooseProvider(folder) {
        this.selectedProvider = folder;
        this.currentView = 'details';
        $wire.selectProvider(folder);
    },
    goBackToMethods() {
        if (this.selectedProvider && this.providerTypeMap[this.selectedProvider]) {
            this.activeTab = this.providerTypeMap[this.selectedProvider];
        }
        this.selectedProvider = null;
        this.currentView = 'methods';
        $wire.selectProvider(null);
    },
    fallbackCopy(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        try {
            document.execCommand('copy');
        } catch (e) {}
        document.body.removeChild(textarea);
    },
    copyToClipboard(text) {
        if (!text) return;
        try {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).catch(() => {
                    this.fallbackCopy(text);
                });
            } else {
                this.fallbackCopy(text);
            }
        } catch (e) {
            this.fallbackCopy(text);
        }
        this.copyToastText = 'Copied: ' + text;
        this.showCopyToast = true;
        if (this._toastTimer) clearTimeout(this._toastTimer);
        this._toastTimer = setTimeout(() => { this.showCopyToast = false; }, 2200);
    }
}" x-init="
    $watch('$wire.selectedProvider', value => {
        if (value) {
            selectedProvider = value;
            currentView = 'details';
        } else if (currentView !== 'details') {
            selectedProvider = null;
            currentView = 'methods';
        }
    });
    if ($wire.selectedProvider) {
        selectedProvider = $wire.selectedProvider;
        currentView = 'details';
    }
">
    <!-- Main Compact Card -->
    <div class="w-full max-w-[430px] relative bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl rounded-2xl sm:rounded-3xl shadow-xl shadow-slate-900/5 dark:shadow-black/40 border border-slate-200/90 dark:border-slate-800 overflow-hidden transition-all">

        <!-- Toast Notification -->
        <div x-show="showCopyToast" x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="absolute top-3 inset-x-4 mx-auto w-fit max-w-[85%] bg-slate-900/90 dark:bg-white/95 text-white dark:text-slate-900 px-3.5 py-2 rounded-full shadow-lg text-xs font-medium z-50 flex items-center gap-2 pointer-events-none">
            <svg class="w-3.5 h-3.5 text-emerald-400 dark:text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
            <span x-text="copyToastText" class="truncate"></span>
        </div>

        <!-- Top Navigation Bar -->
        <div class="px-4 py-3 flex justify-between items-center border-b border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/40">
            <!-- Left: Back Button or Brand Icon -->
            <div class="flex items-center">
                <button type="button" x-show="currentView === 'details'" x-cloak
                    @if (! $selectedProvider) style="display: none;" @endif
                    @click="goBackToMethods()"
                    class="group flex items-center gap-1.5 text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors py-1 px-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">
                    <svg class="w-3.5 h-3.5 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span>Change</span>
                </button>

                <div x-show="currentView === 'methods'"
                    @if ($selectedProvider) style="display: none;" @endif
                    class="flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Secure Checkout</span>
                </div>
            </div>

            <!-- Center/Right: Action Buttons (Support, FAQ, Details) -->
            <div class="flex items-center gap-1 sm:gap-1.5">
                <button type="button" @click="activeModal = 'support'"
                    class="p-1.5 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-xs transition flex items-center gap-1"
                    title="Customer Support">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span class="hidden sm:inline">Support</span>
                </button>

                <button type="button" @click="activeModal = 'faq'"
                    class="p-1.5 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-xs transition flex items-center gap-1"
                    title="Frequently Asked Questions">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="hidden sm:inline">FAQ</span>
                </button>

                <button type="button" @click="activeModal = 'details'"
                    class="p-1.5 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-xs transition flex items-center gap-1"
                    title="Invoice Details">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="hidden sm:inline">Details</span>
                </button>

                <!-- Close -->
                <button type="button" @click="window.close()"
                    class="p-1.5 ml-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition"
                    title="Close">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Header: Brand & Amount Summary Card -->
        <div class="px-4 py-3 bg-gradient-to-br from-slate-50 via-emerald-50/20 to-slate-50 dark:from-slate-800/60 dark:via-slate-800/30 dark:to-slate-900 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="relative w-10 h-10 rounded-xl bg-white dark:bg-slate-800 p-1 shadow-sm border border-slate-200/80 dark:border-slate-700 flex items-center justify-center shrink-0">
                        <img src="{{ asset('assets/imgs/H-Pay.png') }}" class="w-full h-full object-contain" alt="{{ config('app.name') }} Logo">
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <h2 class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                {{ config('app.name') }}
                            </h2>
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-slate-200/70 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-mono">
                                #{{ $invoice->invoice_id }}
                            </span>
                        </div>
                        <div class="flex items-baseline gap-1">
                            <span class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                                {{ number_format($invoice->amount, 2) }}
                            </span>
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                {{ $invoice->currency }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Selected Method Logo / Badge -->
                <div x-cloak x-show="Boolean(selectedProvider)" class="shrink-0">
                    @foreach ($gateways as $type => $method)
                        @foreach ($method['drivers'] as $driver)
                            <div x-cloak
                                x-show="selectedProvider === '{{ $driver['folder'] }}'"
                                wire:key="header-logo-{{ $driver['folder'] }}"
                                class="h-9 px-2 py-1 bg-white dark:bg-slate-800 rounded-xl border border-slate-200/90 dark:border-slate-700 shadow-sm flex items-center justify-center">
                                <img src="{{ route('plugins.image', ['plugin' => $driver['folder']]) }}"
                                    alt="{{ $driver['name'] }}" class="h-6 w-auto object-contain">
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>

        <!-- VIEW 1: Payment Methods Selection -->
        <div x-show="currentView === 'methods'"
            @if ($selectedProvider) style="display: none;" @endif
            x-transition:enter="transition duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            @php
                $initialTab = 'mobile';
                if ($selectedProvider) {
                    foreach ($gateways as $gType => $gData) {
                        foreach ($gData['drivers'] as $d) {
                            if ($d['folder'] === $selectedProvider) {
                                $initialTab = $gType;
                                break 2;
                            }
                        }
                    }
                }
            @endphp
            <!-- Compact Tabs -->
            <div class="flex p-1 mx-3.5 mt-2.5 rounded-xl bg-slate-100 dark:bg-slate-800/80 text-xs font-medium border border-slate-200/60 dark:border-slate-700/60">
                @foreach ($gateways as $type => $method)
                    <button type="button" @click="activeTab = '{{ $type }}'"
                        :class="activeTab === '{{ $type }}' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm font-semibold' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400'"
                        class="flex-1 py-1.5 px-2 rounded-lg transition-all text-center cursor-pointer">
                        {{ $method['name'] }}
                    </button>
                @endforeach
            </div>

            <!-- Methods Grid -->
            <div class="p-3.5 sm:p-4">
                @foreach ($gateways as $type => $method)
                    <div x-cloak x-show="activeTab === '{{ $type }}'"
                        @if ($type !== $initialTab) style="display: none;" @endif
                        class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 mb-3">
                        @forelse ($method['drivers'] as $driver)
                            <button type="button"
                                @click="chooseProvider('{{ $driver['folder'] }}')"
                                class="group relative flex flex-col items-center justify-center p-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800/50 hover:bg-emerald-50/40 dark:hover:bg-slate-800 hover:border-emerald-500 dark:hover:border-emerald-500 hover:shadow-md transition-all cursor-pointer">
                                <div class="h-9 w-full flex items-center justify-center">
                                    <img src="{{ route('plugins.image', ['plugin' => $driver['folder']]) }}"
                                        alt="{{ $driver['name'] }}" class="h-7 max-w-[85%] object-contain group-hover:scale-105 transition-transform">
                                </div>
                                <span class="text-[11px] mt-1 font-medium text-center text-slate-700 dark:text-slate-300 line-clamp-1">
                                    {{ $driver['name'] }}
                                </span>
                            </button>
                        @empty
                            <div class="col-span-full py-6 text-center text-xs text-slate-400 dark:text-slate-500">
                                No payment methods active in this category.
                            </div>
                        @endforelse
                    </div>
                @endforeach

                <!-- Security Note -->
                <div class="flex items-center justify-center gap-1.5 text-[11px] text-slate-400 dark:text-slate-500 pt-0.5">
                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span>Secured with 256-bit SSL encryption</span>
                </div>
            </div>
        </div>

        <!-- VIEW 2: Payment Details & Verification -->
        <div x-cloak x-show="currentView === 'details'"
            @if (! $selectedProvider) style="display: none;" @endif
            x-transition:enter="transition duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <div class="p-3.5 sm:p-4">
                <!-- Driver Instructions View -->
                <div class="mb-2.5">
                    @foreach ($gateways as $type => $method)
                        @foreach ($method['drivers'] as $driver)
                            <div x-cloak
                                x-show="selectedProvider === '{{ $driver['folder'] }}'"
                                wire:key="instruction-{{ $driver['folder'] }}">
                                @include($driver['instruction_view'])
                            </div>
                        @endforeach
                    @endforeach
                </div>

                <!-- Verification In-Progress Status Card -->
                @if ($isVerifying)
                    <div class="mb-3 p-3 rounded-xl bg-emerald-50/90 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-700/60 text-xs">
                        <div class="flex items-start gap-2.5">
                            <div class="p-1.5 rounded-lg bg-emerald-100 dark:bg-emerald-900/60 text-emerald-600 dark:text-emerald-300 shrink-0">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <div class="space-y-0.5 flex-1">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-bold text-emerald-950 dark:text-emerald-100">Verifying Transfer with AI</h4>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-200/70 dark:bg-emerald-800 text-emerald-800 dark:text-emerald-200">
                                        Live
                                    </span>
                                </div>
                                <p class="text-emerald-800 dark:text-emerald-300 text-[11px] leading-relaxed">
                                    Our AI is reconciling your receipt against bank confirmation SMS. This usually takes 10–30 seconds.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Verification Failed / Timeout Support Card -->
                @if ($verificationFailed)
                    <div class="mb-3 p-3 rounded-xl bg-rose-50/90 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 text-xs">
                        <div class="flex items-start gap-2.5">
                            <div class="p-1.5 rounded-lg bg-rose-100 dark:bg-rose-900/60 text-rose-600 dark:text-rose-300 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="space-y-1.5 flex-1">
                                <h4 class="font-bold text-rose-950 dark:text-rose-100">Verification Incomplete</h4>
                                <p class="text-rose-800 dark:text-rose-300 text-[11px] leading-relaxed">
                                    {{ $verificationError ?? 'Could not verify payment within 10 minutes. If you completed the transfer, please contact our support team.' }}
                                </p>
                                <div class="pt-1 flex items-center gap-2">
                                    <button type="button" @click="activeModal = 'support'"
                                        class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-semibold text-[11px] shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <span>Contact Support</span>
                                    </button>
                                    <button type="button" wire:click="resetVerification"
                                        class="px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg font-medium text-[11px] transition cursor-pointer">
                                        Try Again
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- ==================== BANK PAYMENT SECTION ==================== -->
                <!-- Bank payments DO NOT have Transaction ID field, only Screenshot Upload -->
                <div x-cloak x-show="isBank"
                    class="space-y-2 mb-3">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold text-slate-800 dark:text-slate-200">
                            Transfer Receipt / Screenshot <span class="text-rose-500">*</span>
                        </label>
                        <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Verified by AI
                        </span>
                    </div>

                    <!-- Screenshot File Dropzone -->
                    <div class="relative">
                        @if ($screenshot)
                            <!-- Uploaded State Preview -->
                            <div class="p-2 sm:p-2.5 rounded-xl bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-700/60 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5 overflow-hidden">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center shrink-0 overflow-hidden">
                                        @if (method_exists($screenshot, 'temporaryUrl'))
                                            <img src="{{ $screenshot->temporaryUrl() }}" class="w-full h-full object-cover" alt="Preview">
                                        @else
                                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="overflow-hidden">
                                        <p class="text-xs font-semibold text-emerald-950 dark:text-emerald-200 truncate">
                                            {{ $screenshot->getClientOriginalName() }}
                                        </p>
                                        <p class="text-[10px] text-emerald-700 dark:text-emerald-400">
                                            Ready for AI verification
                                        </p>
                                    </div>
                                </div>
                                <button type="button" wire:click="removeScreenshot"
                                    class="p-1 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-white/60 dark:hover:bg-slate-800 transition cursor-pointer"
                                    title="Remove Screenshot">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        @else
                            <!-- Dropzone / Selector -->
                            <label class="hp-dropzone group relative flex items-center justify-center gap-3 p-2.5 sm:p-3 border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-emerald-500 dark:hover:border-emerald-500 rounded-xl bg-slate-50/50 dark:bg-slate-800/40 hover:bg-emerald-50/20 dark:hover:bg-slate-800/80 cursor-pointer transition-all">
                                <input type="file" wire:model="screenshot" accept="image/*" class="sr-only" :disabled="$wire.isVerifying">
                                <div class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                </div>
                                <div class="text-left">
                                    <span class="block text-xs font-semibold text-slate-800 dark:text-slate-200">
                                        Click to upload transfer screenshot
                                    </span>
                                    <span class="block text-[10px] text-slate-400 dark:text-slate-500">
                                        Receipt slip or app confirmation (PNG, JPG up to 10MB)
                                    </span>
                                </div>
                            </label>
                        @endif

                        <!-- Livewire Upload Progress Spinner -->
                        <div wire:loading wire:target="screenshot" class="mt-1.5 text-center text-xs text-emerald-600 dark:text-emerald-400 font-medium flex items-center justify-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Uploading image...</span>
                        </div>
                    </div>
                </div>

                <!-- ==================== MFS PAYMENT SECTION ==================== -->
                <!-- MFS payments HAVE Transaction ID field + OPTIONAL Screenshot Upload -->
                <div x-cloak x-show="!isBank"
                    class="space-y-2.5 mb-3">
                    <!-- Transaction ID Field -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-800 dark:text-slate-200">
                                Transaction ID
                            </label>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500">From confirmation SMS</span>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <span class="font-mono text-xs">#</span>
                            </div>
                            <input wire:model="transactionId" type="text"
                                placeholder="Enter Transaction ID (e.g. DC61R98YS5)"
                                class="w-full pl-8 pr-4 py-2 text-xs sm:text-sm font-mono tracking-wider uppercase border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white placeholder:normal-case placeholder:font-sans placeholder:text-slate-400 transition"
                                :disabled="$wire.isVerifying">
                        </div>
                    </div>

                    <!-- Optional Screenshot Upload for MFS -->
                    <div class="pt-0.5">
                        @if ($screenshot)
                            <!-- Uploaded State for MFS -->
                            <div class="p-2 rounded-xl bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-700/60 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2 overflow-hidden">
                                    <div class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center shrink-0 overflow-hidden">
                                        @if (method_exists($screenshot, 'temporaryUrl'))
                                            <img src="{{ $screenshot->temporaryUrl() }}" class="w-full h-full object-cover" alt="Preview">
                                        @else
                                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="overflow-hidden">
                                        <p class="text-xs font-medium text-emerald-950 dark:text-emerald-200 truncate">
                                            {{ $screenshot->getClientOriginalName() }}
                                        </p>
                                        <span class="text-[10px] text-emerald-700 dark:text-emerald-400 font-medium">Screenshot attached (Optional)</span>
                                    </div>
                                </div>
                                <button type="button" wire:click="removeScreenshot"
                                    class="p-1 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg transition cursor-pointer"
                                    title="Remove Screenshot">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @else
                            <div x-data="{ expanded: false }">
                                <button type="button" @click="expanded = !expanded"
                                    class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1 cursor-pointer">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span x-text="expanded ? 'Hide screenshot upload' : '+ Upload payment screenshot (Optional)'"></span>
                                </button>

                                <div x-show="expanded" x-collapse class="mt-1.5">
                                    <label class="flex items-center justify-center gap-2.5 p-2 border border-dashed border-slate-300 dark:border-slate-700 hover:border-emerald-500 rounded-xl bg-slate-50/50 dark:bg-slate-800/40 cursor-pointer transition text-center">
                                        <input type="file" wire:model="screenshot" accept="image/*" class="sr-only" :disabled="$wire.isVerifying">
                                        <span class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                            Attach MFS Confirmation Screenshot
                                        </span>
                                        <span class="text-[10px] text-slate-400">
                                            (Auto-extracts TrxID)
                                        </span>
                                    </label>
                                </div>
                            </div>
                        @endif

                        <div wire:loading wire:target="screenshot" class="mt-1.5 text-center text-xs text-emerald-600 dark:text-emerald-400 font-medium flex items-center justify-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Uploading screenshot...</span>
                        </div>
                    </div>
                </div>

                <!-- Verify Action Button -->
                <button type="button" wire:click="verifyTransaction" :disabled="$wire.isVerifying"
                    class="hp-action-btn w-full relative overflow-hidden bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 active:scale-[0.99] text-white font-semibold py-2.5 px-4 rounded-xl shadow-md shadow-emerald-600/20 hover:shadow-emerald-600/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 cursor-pointer">
                    <template x-if="$wire.isVerifying">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <span class="text-xs sm:text-sm tracking-wide" x-text="$wire.isVerifying ? 'Verifying...' : 'Verify Payment'">
                        Verify Payment
                    </span>
                </button>

                <!-- Security & Trust Info -->
                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800 text-center space-y-0.5">
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 flex items-center justify-center gap-1.5">
                        <svg class="w-3 h-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span>Protected by 256-bit bank encryption</span>
                    </p>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500">
                        Powered by <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ config('app.name') }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== POPUP MODALS ==================== -->

    <!-- SUPPORT MODAL -->
    <div x-show="activeModal === 'support'" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        <div @click.away="activeModal = null" class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    Payment Support
                </h3>
                <button type="button" @click="activeModal = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="space-y-3 text-xs text-slate-600 dark:text-slate-300">
                <p>Have an issue with your transaction or need assistance completing your payment?</p>
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/70 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Email:</span>
                        <a href="mailto:support@hotashpay.test" class="font-semibold text-emerald-600 dark:text-emerald-400">support@hotashpay.test</a>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Helpline:</span>
                        <span class="font-mono font-semibold text-slate-800 dark:text-slate-200">+880 1700-000000</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Hours:</span>
                        <span>24/7 Automated AI Verification</span>
                    </div>
                </div>
            </div>
            <button type="button" @click="activeModal = null"
                class="w-full py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-medium text-xs rounded-xl transition">
                Close
            </button>
        </div>
    </div>

    <!-- FAQ MODAL -->
    <div x-show="activeModal === 'faq'" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        <div @click.away="activeModal = null" class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Frequently Asked Questions
                </h3>
                <button type="button" @click="activeModal = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="space-y-2.5 text-xs text-slate-600 dark:text-slate-300 max-h-72 overflow-y-auto pr-1">
                <div>
                    <p class="font-semibold text-slate-800 dark:text-white mb-0.5">How does Bank Transfer verification work?</p>
                    <p class="text-slate-500 dark:text-slate-400">Transfer the exact amount to the displayed bank account, then upload your transfer receipt screenshot. Our AI reads the details and matches it instantly.</p>
                </div>
                <div>
                    <p class="font-semibold text-slate-800 dark:text-white mb-0.5">Where do I find the MFS Transaction ID?</p>
                    <p class="text-slate-500 dark:text-slate-400">It is sent in the confirmation SMS from bKash or Nagad, or shown on your app confirmation screen (e.g. 10 alphanumeric characters).</p>
                </div>
                <div>
                    <p class="font-semibold text-slate-800 dark:text-white mb-0.5">How long does verification take?</p>
                    <p class="text-slate-500 dark:text-slate-400">Usually 5 to 30 seconds after the SMS notification is received by our server.</p>
                </div>
            </div>
            <button type="button" @click="activeModal = null"
                class="w-full py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-medium text-xs rounded-xl transition">
                Close
            </button>
        </div>
    </div>

    <!-- INVOICE DETAILS MODAL -->
    <div x-show="activeModal === 'details'" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        <div @click.away="activeModal = null" class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Invoice Summary
                </h3>
                <button type="button" @click="activeModal = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/70 space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-500">Invoice ID:</span>
                    <span class="font-mono font-semibold text-slate-900 dark:text-white">#{{ $invoice->invoice_id }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Customer:</span>
                    <span class="font-semibold text-slate-900 dark:text-white">{{ $invoice->client_name }}</span>
                </div>
                @if ($invoice->client_email)
                    <div class="flex justify-between">
                        <span class="text-slate-500">Email:</span>
                        <span class="text-slate-900 dark:text-white">{{ $invoice->client_email }}</span>
                    </div>
                @endif
                @if ($invoice->client_phone)
                    <div class="flex justify-between">
                        <span class="text-slate-500">Phone:</span>
                        <span class="text-slate-900 dark:text-white">{{ $invoice->client_phone }}</span>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-slate-500">Created:</span>
                    <span class="text-slate-700 dark:text-slate-300">{{ $invoice->created_at?->format('M d, Y h:i A') }}</span>
                </div>
                <div class="flex justify-between pt-2 border-t border-slate-200/60 dark:border-slate-700/60">
                    <span class="font-semibold text-slate-700 dark:text-slate-300">Total Payable:</span>
                    <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $invoice->amount }} {{ $invoice->currency }}</span>
                </div>
            </div>
            <button type="button" @click="activeModal = null"
                class="w-full py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-medium text-xs rounded-xl transition">
                Close
            </button>
        </div>
    </div>
</div>
