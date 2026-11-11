<x-filament-panels::page>
    <x-filament::tabs>
        @foreach (['all' => 'All', ...\Workbench\App\Filament\Pages\Invoices::STATUSES] as $tab => $label)
            <x-filament::tabs.item
                :active="$activeTab === $tab"
                wire:click="$set('activeTab', '{{ $tab }}')"
            >
                {{ $label }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>

    {{ $this->table }}
</x-filament-panels::page>
