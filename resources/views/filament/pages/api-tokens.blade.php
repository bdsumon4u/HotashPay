<x-filament-panels::page>
    <div class="space-y-6">
        @if ($plainTextToken)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex justify-between items-center">
                        <div>New API Token</div>
                        <x-filament::button color="gray" icon="heroicon-m-x-mark" size="sm"
                            wire:click="closeTokenModal">
                            Close
                        </x-filament::button>
                    </div>
                </x-slot>

                <x-slot name="description">
                    Please copy your new API token. For your security, it won't be shown again.
                </x-slot>

                <div class="space-y-4">
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-center justify-between gap-4">
                            <code class="text-sm font-mono break-all">{{ $plainTextToken }}</code>
                            <x-filament::button color="gray" icon="heroicon-m-clipboard" size="sm"
                                x-on:click="
                                    navigator.clipboard.writeText('{{ $plainTextToken }}');
                                    $tooltip('Copied!', { timeout: 2000 });
                                ">
                                Copy
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        @endif

        {{ $this->table }}
    </div>
</x-filament-panels::page>
