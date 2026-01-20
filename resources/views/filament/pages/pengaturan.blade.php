<x-filament-panels::page>
     <x-filament-panels::form wire:submit="save"> 
          <x-filament-panels::form.actions 
            :actions="$this->getFormActions()"
        /> 
        </x-filament-panels::form>
{{ $this->form }}


</x-filament-panels::page>
