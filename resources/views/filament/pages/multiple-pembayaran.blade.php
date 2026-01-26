<x-filament-panels::page>
    <p>Buat pembayaran untuk beberapa tagihan dalam sekali input</p>
    <small style="color: red;">*) hati-hati dalam pemilihan tagihan, jangan sampai tagihan dipilih berulang. Periksa kembali sebelum menyimpan</small>
    <x-filament-panels::form wire:submit="create">
        {{ $this->form }}

        <div style="width: 100%; display: flex; flex-direction: row-reverse;">
            <x-filament-panels::form.actions
            :actions="$this->getFormActions()"
        />
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>
