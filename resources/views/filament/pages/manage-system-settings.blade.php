<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-800">
            <x-filament::button type="submit" size="md" icon="heroicon-m-check">
                Save System Settings
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
