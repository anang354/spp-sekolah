<?php

namespace App\Filament\Pages;

use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Support\Exceptions\Halt;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;

class Pengaturan extends Page implements HasForms
{
    use InteractsWithForms;
    public ?array $data = []; 

    public ?\App\Models\Pengaturan $record = null;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.pengaturan';

    public static function canAccess() : bool
    {
        return auth()->user()->role === 'admin' || auth()->user()->role === 'editor';
    }

    public function mount(): void 
    {
        $this->record = \App\Models\Pengaturan::firstOrCreate([]);

        // Isi data dengan atribut record
        $this->form->fill($this->record->attributesToArray());
    }
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Section::make('Data Sekolah')
                    ->schema([
                        Components\TextInput::make('nama_sekolah')->label('Nama Sekolah'),
                        Components\TextInput::make('alamat_sekolah')->label('Alamat Sekolah'),
                        Components\TextInput::make('telepon_sekolah')->label('Telepon Sekolah')->numeric(),
                        FileUpload::make('logo_sekolah')
                        ->disk('public')
                        ->directory('logo-sekolah')
                        ->image()
                        ->imageEditor()
                        ->getUploadedFileNameForStorageUsing(function ($file) {
                            return "logo_sekolah." . $file->getClientOriginalExtension();
                        }),
                    ])->columns(2)->visible(fn () => auth()->user()->role === 'admin'),
                Components\Section::make('Pengaturan WhatsApp')
                    ->schema([
                        Components\TextInput::make('token_whatsapp')->label('Token WhatsApp')->readOnly(fn () => auth()->user()->role !== 'admin'),
                        Components\Toggle::make('whatsapp_active')->label('Aktifkan WhatsApp'),
                        Components\Textarea::make('pesan1')->label('Pesan 1')->helperText('Pesan untuk broadcast tagihan')->rows(5)->columnSpanFull(),
                        Components\Textarea::make('pesan2')->label('Pesan 2')->helperText('Pesan untuk follow up tagihan')->rows(5)->columnSpanFull(),
                        Components\Textarea::make('pesan3')->label('Pesan 3')->rows(5)->helperText('Pesan untuk untuk kirim total tagihan di menu siswa')->columnSpanFull(),
                        Components\Placeholder::make('info')
                        ->content(new \Illuminate\Support\HtmlString('
                        <p>Gunakan hanya parameter dibawah ini untuk mengisi pesan otomatis&nbsp;</p>
<p><strong>Untuk Pesan1 dan Pesan 2</strong></p>
<small><span style="color: #ff0000;">{nama_siswa},&nbsp;{nama_wali},&nbsp;{nama_kelas}</span></small>
<small><span style="color: #ff0000;">{daftar_biaya},&nbsp;{jenis_keuangan},&nbsp;{tahun},&nbsp;{bulan},&nbsp;{jatuh_tempo},&nbsp;{jumlah_tagihan},&nbsp;{jumlah_diskon},&nbsp;{total_tagihan},&nbsp;{status}</span></small>
<p>&nbsp;</p>
<p><strong>Untuk Pesan3</strong></p>
<small><span style="color: #ff0000;">{nama_siswa},&nbsp;{nama_wali},&nbsp;{nama_kelas}, {total_tagihan_belum_lunas}</span></small>
                                                ')),
                    ])->columns(2),
            ])
            ->statePath('data')->model($this->record);
    }
    protected function getFormActions(): array 
    {
        return [
            \Filament\Actions\Action::make('save')->submit('save')->label('Simpan Pengaturan')->color('primary'),
        ];
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();
 
            $this->record->update($data);
        } catch (Halt $exception) {
            return;
        }
 
        \Filament\Notifications\Notification::make() 
            ->success()
            ->title('Berhasil menyimpan data')
            ->send(); 
    }
}
