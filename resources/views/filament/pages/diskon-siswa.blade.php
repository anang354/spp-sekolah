<x-filament-panels::page>
    <form wire:submit.prevent="submit" class="space-y-4">
        {{ $this->form }}
    
        <x-filament::button type="submit">
            Simpan Diskon Siswa
        </x-filament::button>
    </form>

    <h1>Daftar Siswa yang memiliki diskon</h1>
  {{$this->table}}

  <x-filament::modal
    :visible="$isEditModalOpen"
    slide-over
    max-width="lg"
    wire:close="$set('isEditModalOpen', false)"
>
    <x-slot name="header">
        <h2 class="text-xl font-bold">Edit Diskon Siswa</h2>
    </x-slot>

    <div class="space-y-4">
        <form wire:submit.prevent="updateDiskon">
            <x-filament::input.wrapper>
                <select wire:model="editDiskonIds" id="editDiskonIds" multiple class="w-full rounded">
                    @foreach(\App\Models\Diskon::pluck('nama_diskon', 'id') as $id => $nama)
                        <option value="{{ $id }}">{{ $nama }}</option>
                    @endforeach
                </select>
                @error('editDiskonIds') <div class="text-danger text-sm">{{ $message }}</div> @enderror
            </x-filament::input.wrapper>

            <x-filament::button type="submit" color="primary">
                Simpan Perubahan
            </x-filament::button>
        </form>
    </div>
</x-filament::modal>
</x-filament-panels::page>
