@push('styles')
<style>
    .fi-simple-main {
        padding: 0;
    }
</style>
@endpush

<div class="flex items-center justify-center" x-data="{
    currentView: 'methods',
    selectedMethod: null,
    transactionId: '',
    showCopyToast: false,
    activeTab: 'mobile',
    copyToClipboard(text) {
        navigator.clipboard.writeText(text);
        this.showCopyToast = true;
        setTimeout(() => { this.showCopyToast = false; }, 2000);
    }
}">
    <div class="w-full max-w-lg relative bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <!-- Toast Notification -->
        <div 
            x-show="showCopyToast"
            x-transition
            class="absolute top-1 right-1 bg-green-500 text-white px-4 py-3 rounded-lg shadow-lg z-50"
            @click="showCopyToast = false"
            style="display: none;">
            ✓ Copied to clipboard!
        </div>
        <!-- Common Header -->
        <div class="p-4 flex justify-between items-center border-b border-gray-200 dark:border-gray-700">
            <button x-show="currentView === 'methods'">
                <svg class="w-6 h-6 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 22V12h6v10" />
                </svg>
            </button>
            <button x-cloak x-show="currentView === 'details'" @click="currentView = 'methods'; selectedMethod = null;"
                class="text-green-500 hover:text-green-600 dark:text-green-400 dark:hover:text-green-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </button>
            <div class="flex gap-3">
                <button class="text-blue-500 hover:text-blue-600 text-sm flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Support</span>
                </button>
                <button class="text-blue-500 hover:text-blue-600 text-sm flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>FAQ</span>
                </button>
                <button class="text-blue-500 hover:text-blue-600 text-sm flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Details</span>
                </button>
            </div>
            <button @click="window.close()"
                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Header -->
        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/50">
            <div class="flex items-center gap-4">
                <img src="{{ asset('assets/imgs/H-Pay.png') }}" class="w-12 h-12 rounded-md" alt="{{ config('app.name') }} Logo">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ config('app.name') }}</h2>
                    <p class="text-xl font-bold text-green-600 dark:text-green-400">
                        {{ $invoice->amount }} {{ $invoice->currency }}</p>
                </div>
                <!-- Method Logo -->
                <div class="flex justify-center ml-auto">
                    <div class="text-6xl" x-text="selectedMethod?.logo"></div>
                </div>
            </div>
        </div>

        <!-- Payment Methods View Body -->
        <div x-show="currentView === 'methods'">
            <!-- Tabs -->
            <div class="flex border-b border-gray-200 dark:border-gray-700">
                <button @click="activeTab = 'mobile'"
                    :class="activeTab === 'mobile' ? 'border-green-500 text-green-600 dark:text-green-400' :
                        'border-transparent text-gray-500 dark:text-gray-400'"
                    class="flex-1 py-3 px-2 border-b-2 font-medium text-sm transition-colors">
                    Mobile Banking
                </button>
                <button @click="activeTab = 'ibanking'"
                    :class="activeTab === 'ibanking' ? 'border-green-500 text-green-600 dark:text-green-400' :
                        'border-transparent text-gray-500 dark:text-gray-400'"
                    class="flex-1 py-3 px-2 border-b-2 font-medium text-sm transition-colors">
                    Net Banking
                </button>
                <button @click="activeTab = 'international'"
                    :class="activeTab === 'international' ? 'border-green-500 text-green-600 dark:text-green-400' :
                        'border-transparent text-gray-500 dark:text-gray-400'"
                    class="flex-1 py-3 px-2 border-b-2 font-medium text-sm transition-colors">
                    International
                </button>
            </div>

            <!-- Payment Methods Grid -->
            <div class="p-6">
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <button @click="selectedMethod = '_bKash-Personal'; currentView = 'details'"
                        class="flex flex-col items-center p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg hover:border-green-500 dark:hover:border-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 transition-all">
                        <img src="https://freelogopng.com/images/all_img/1656227518bkash-logo-png.png" alt="bKash Logo" class="h-10">
                        <span class="text-xs mt-1 font-medium text-center text-gray-700 dark:text-gray-300">bKash Personal</span>
                    </button>
                    <button @click="selectedMethod = '_bKash-Merchant'; currentView = 'details'"
                        class="flex flex-col items-center p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg hover:border-green-500 dark:hover:border-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 transition-all">
                        <img src="https://freelogopng.com/images/all_img/1656235602bkash-logo-black.png" alt="bKash Logo" class="h-10">
                        <span class="text-xs mt-1 font-medium text-center text-gray-700 dark:text-gray-300">bKash Merchant</span>
                    </button>
                    <button @click="selectedMethod = '_Nagad-Personal'; currentView = 'details'"
                        class="flex flex-col items-center p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg hover:border-green-500 dark:hover:border-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 transition-all">
                        <img src="https://freelogopng.com/images/all_img/1683082228nagad-transparent-logo.png" alt="Nagad Logo" class="h-10">
                        <span class="text-xs mt-1 font-medium text-center text-gray-700 dark:text-gray-300">Nagad Personal</span>
                    </button>
                    <button @click="selectedMethod = '_Rocket-Personal'; currentView = 'details'"
                        class="flex flex-col items-center p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg hover:border-green-500 dark:hover:border-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 transition-all">
                        <img src="https://images.seeklogo.com/logo-png/31/1/dutch-bangla-rocket-logo-png_seeklogo-317692.png" alt="Rocket Logo" class="h-10">
                        <span class="text-xs mt-1 font-medium text-center text-gray-700 dark:text-gray-300">Rocket Personal</span>
                    </button>
                    <button @click="selectedMethod = '_Upay-Personal'; currentView = 'details'"
                        class="flex flex-col items-center p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg hover:border-green-500 dark:hover:border-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 transition-all">
                        <img src="https://images.seeklogo.com/logo-png/40/1/upay-logo-png_seeklogo-404483.png" alt="Upay Logo" class="h-10">
                        <span class="text-xs mt-1 font-medium text-center text-gray-700 dark:text-gray-300">Upay Personal</span>
                    </button>
                    <button @click="selectedMethod = '_Cellfin-Personal'; currentView = 'details'"
                        class="flex flex-col items-center p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg hover:border-green-500 dark:hover:border-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 transition-all">
                        <img src="https://tmm.edu.bd/assets/images/CellFin.png" alt="Cellfin Logo" class="h-10">
                        <span class="text-xs mt-1 font-medium text-center text-gray-700 dark:text-gray-300">Cellfin Personal</span>
                    </button>
                </div>

                <!-- Pay Button -->
                <button
                    class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Pay {{ $invoice->amount }} {{ $invoice->currency }}
                </button>

                <!-- Security Note -->
                <p class="text-xs text-center text-gray-500 dark:text-gray-400 mt-4">
                    Your payment is secured with 256-bit encryption
                </p>
            </div>
        </div>

        <!-- Payment Details View Body -->
        <div x-cloak x-show="selectedMethod">
            <div class="px-6 py-6">
                <!-- Instructions -->
                @foreach(File::allFiles(resource_path('views/filament/pages/_instructions')) as $instruction)
                <template x-if="selectedMethod == '{{ str($instruction->getFilename())->replace('.blade.php', '')->toString() }}'">
                    @include(
                        str($instruction->getRelativePathname())
                            ->replace('.blade.php', '')
                            ->replace('/', '.')
                            ->prepend('filament.pages._instructions.')
                            ->toString()
                    )
                </template>
                @endforeach

                <!-- Transaction ID Input -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Transaction
                        ID</label>
                    <input x-model="transactionId" type="text" placeholder="Enter Transaction ID"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>

                <!-- Verify Button -->
                <button
                    class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors">
                    Verify
                </button>

                <!-- Security Note -->
                <p class="text-xs text-center text-gray-500 dark:text-gray-400 mt-4">
                    Your payment is secured with 256-bit encryption
                </p>
                <p class="text-xs text-center text-gray-500 dark:text-gray-400 mt-2">
                    Powered by <span class="text-green-600 dark:text-green-400 font-semibold">HotashPay</span>
                </p>
            </div>
        </div>
    </div>
</div>
