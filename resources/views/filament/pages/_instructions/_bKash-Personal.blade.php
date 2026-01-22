<div class="space-y-4 mb-6">
    <div class="flex gap-3">
        <span
            class="flex-shrink-0 w-6 h-6 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full flex items-center justify-center text-sm font-semibold">1</span>
        <p class="text-sm text-gray-700 dark:text-gray-300">Dial <span class="font-semibold">*247#</span>
            or open the <span class="font-semibold text-green-600 dark:text-green-400">bKash</span> app.</p>
    </div>

    <div class="flex gap-3">
        <span
            class="flex-shrink-0 w-6 h-6 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full flex items-center justify-center text-sm font-semibold">2</span>
        <p class="text-sm text-gray-700 dark:text-gray-300">Choose: <span
                class="font-semibold text-green-600 dark:text-green-400">Send Money</span></p>
    </div>

    <div class="flex gap-3">
        <span
            class="flex-shrink-0 w-6 h-6 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full flex items-center justify-center text-sm font-semibold">3</span>
        <div class="flex-1">
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">Enter the Number:</p>
            <div class="flex items-center gap-2">
                <code
                    class="flex-1 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded px-3 py-2 text-sm font-mono text-green-600 dark:text-green-400">01624093099</code>
                <button @click="copyToClipboard('01624093099')"
                    class="px-3 py-2 bg-green-500 hover:bg-green-600 text-white text-xs rounded transition-colors">
                    Copy
                </button>
            </div>
        </div>
    </div>

    <div class="flex gap-3">
        <span
            class="flex-shrink-0 w-6 h-6 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full flex items-center justify-center text-sm font-semibold">4</span>
        <div class="flex-1">
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">Enter the Amount:</p>
            <div class="flex items-center gap-2">
                <code
                    class="flex-1 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded px-3 py-2 text-sm font-mono text-green-600 dark:text-green-400">{{ $invoice->amount }}
                    BDT</code>
                <button @click="copyToClipboard({{ $invoice->amount }})"
                    class="px-3 py-2 bg-green-500 hover:bg-green-600 text-white text-xs rounded transition-colors">
                    Copy
                </button>
            </div>
        </div>
    </div>

    <div class="flex gap-3">
        <span
            class="flex-shrink-0 w-6 h-6 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full flex items-center justify-center text-sm font-semibold">5</span>
        <p class="text-sm text-gray-700 dark:text-gray-300">Now enter your <span
                class="font-semibold text-green-600 dark:text-green-400" x-text="selectedMethod?.name"></span> PIN to
            confirm.</p>
    </div>

    <div class="flex gap-3">
        <span
            class="flex-shrink-0 w-6 h-6 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full flex items-center justify-center text-sm font-semibold">6</span>
        <p class="text-sm text-gray-700 dark:text-gray-300">Put the <span
                class="font-semibold text-green-600 dark:text-green-400">Transaction ID</span> in the
            box
            below and press <span class="font-semibold">Verify</span></p>
    </div>
</div>
