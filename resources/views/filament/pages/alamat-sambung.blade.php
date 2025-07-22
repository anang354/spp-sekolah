<x-filament-panels::page>
    @if(auth()->user()->role !== 'viewer')
    <x-filament-panels::form wire:submit="save"> 
        {{ $this->form }}
 
        <div style="width: 100%; display: flex; flex-direction: row-reverse;">
            <x-filament-panels::form.actions 
            :actions="$this->getFormActions()"
        /> 
        </div>
    </x-filament-panels::form>
    @endif
   {{ $this->table}}
</x-filament-panels::page>
