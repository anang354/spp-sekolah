<?php

namespace App\Filament\Pages;

use \Filament\Forms\Components\Repeater;
use App\Models\Pembayaran;
use Carbon\Carbon;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\DB;

class MultiplePembayaran extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $model = Pembayaran::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static string $view = 'filament.pages.multiple-pembayaran';
    public ?array $data = [];

    public static function canAccess() : bool
    {
        return auth()->user()->role === 'admin' || auth()->user()->role === 'editor';
    }

    public function mount(): void
    {
        $this->form->fill([]); // Or with specific data: $this->form->fill($this->record->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make()
                    ->columns([
                        'sm' => 4,
                        'xl' => 6,
                        '2xl' => 7,
                    ])
                    ->schema([
                        Radio::make('metode_pembayaran')
                            ->required()
                            ->options([
                                'tunai' => 'Tunai',
                                'transfer' => 'Transfer',
                            ])
                            ->columnSpan([
                                'sm' => 2,
                                'xl' => 2,
                                '2xl' => 1,
                            ]),
                        DatePicker::make('tanggal_pembayaran')
                            ->default(now())
                            ->required()
                            ->columnSpan([
                                'sm' => 2,
                                'xl' => 2,
                                '2xl' => 3,
                            ]),
                        TextInput::make('keterangan')
                            ->columnSpan([
                                'sm' => 'full',
                                'xl' => 2,
                                '2xl' => 3,
                            ]),
                    ]),
                Select::make('siswa_id')
                    ->label('Pilih Siswa')
                    ->options(\App\Models\Siswa::all()->pluck('nama', 'id')->toArray())
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('Tagihan', [])),

                Repeater::make('Tagihan')
                    ->label('Daftar Tagihan')
                    ->addActionLabel('Tambah Tagihan yang akan dibayar')
                    ->reactive()
                    ->afterStateUpdated(fn (Set $set, Get $get) => self::updateTotalSemua($set, $get)) // Update saat baris dihapus
                    ->schema([
                        Select::make('tagihan_id')
                            ->label('Tagihan')
                            ->required()
                            ->reactive()
                            ->distinct()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->options(function (Get $get) {
                                $siswaId = $get('../../siswa_id');
                                if (!$siswaId) return [];

                                return \App\Models\Tagihan::where('siswa_id', $siswaId)
                                    ->whereRaw('(jumlah_netto - (SELECT COALESCE(SUM(jumlah_dibayar), 0) FROM pembayarans WHERE pembayarans.tagihan_id = tagihans.id)) > 0')
                                    ->get()
                                    ->mapWithKeys(function ($tagihan) {
                                        $bulan = \Carbon\Carbon::createFromDate(null, $tagihan->periode_bulan, 1)->translatedFormat('F');
                                        return [$tagihan->id => "{$tagihan->nama_tagihan} - {$bulan} {$tagihan->periode_tahun} - Rp. " . number_format($tagihan->sisa_tagihan, 0, ",", ".")];
                                    });
                            })
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if ($state) {
                                    $tagihan = \App\Models\Tagihan::find($state);
                                    if ($tagihan) {
                                        $set('jumlah_dibayar', $tagihan->sisa_tagihan);
                                    }
                                }
                                // Paksa update total semua setelah tagihan dipilih dan nominal otomatis terisi
                                self::updateTotalSemua($set, $get);
                            }),

                        TextInput::make('jumlah_dibayar')
                            ->numeric()
                            ->required()
                            ->hint(fn ($state) => $state ? \App\Helpers\Terbilang::make($state) : null)
                            ->hintColor('primary')
                            ->live(onBlur: true) // Mengirim state ke server saat kursor keluar dari box
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $tagihanId = $get('tagihan_id');
                                    if (!$tagihanId) return;

                                    $tagihan = \App\Models\Tagihan::find($tagihanId);
                                    if (!$tagihan) return;

                                    $totalTerbayar = \App\Models\Pembayaran::where('tagihan_id', $tagihanId)->sum('jumlah_dibayar'); //
                                    $sisaTagihan = $tagihan->jumlah_netto - $totalTerbayar; //

                                    if ($value > $sisaTagihan) {
                                        $fail("Nominal melebihi sisa tagihan. Maksimal adalah Rp. " . number_format($sisaTagihan, 0, ',', '.'));
                                    }
                                },
                            ])
                            ->afterStateUpdated(fn (Set $set, Get $get) => self::updateTotalSemua($set, $get)),
                    ])
                    ->columns(2),
                TextInput::make('total_semua_dibayar')
                    ->label('Total Semua Dibayar')
                    ->disabled()
                    ->dehydrated(true)
                    ->prefix('Rp. ')
                    ->numeric()
                    ->default(0)
                    ->live()
                    ->hint(fn ($get) => \App\Helpers\Terbilang::make((int) $get('total_semua_dibayar')))
                    ->hintColor('warning'),
                FileUpload::make('bukti_bayar')
                    ->disk('local')
                    ->directory('bukti-bayar')
                    ->downloadable() // <<< Penting: Mengizinkan file didownload dari Filament
                    ->previewable() // <<< Opsional: Memungkinkan pratinjau gambar atau PDF (jika didukung browser)
                    ->visibility('private')
            ]);
    }
    public static function updateTotalSemua(Set $set, Get $get): void
    {
        // Mengambil state 'Tagihan' dari level yang tepat
        // Jika dipanggil dari dalam repeater, gunakan ../../Tagihan
        // Jika dipanggil dari level repeater, gunakan Tagihan
        $repeaterState = $get('Tagihan') ?? $get('../../Tagihan') ?? [];

        $total = collect($repeaterState)
            ->map(fn ($item) => (int) ($item['jumlah_dibayar'] ?? 0))
            ->sum();

        // Set field total_semua_dibayar yang berada di luar repeater
        $set('../../total_semua_dibayar', $total);
    }
    protected function getFormActions(): array
    {
        return [
            // Tombol 1: Buat (Simpan & Redirect)
            Action::make('create')
                ->label('Buat Pembayaran')
                ->icon('heroicon-m-check')
                ->submit('create'), // Memanggil function create()

            // Tombol 2: Buat & Buat Lainnya (Simpan & Reset Form)
            Action::make('createAnother')
                ->label('Buat & Buat Lainnya')
                ->icon('heroicon-m-plus')
                ->color('gray')
                ->action('createAnother'), // Memanggil function createAnother()

            // Tombol 3: Batal (Kembali ke Index)
            Action::make('cancel')
                ->label('Batal')
                ->color('gray')
                ->url(function () {
                    // Arahkan kembali ke resource index (sesuaikan route Anda)
                    return \App\Filament\Resources\PembayaranResource::getUrl('index');
                    // Atau jika hardcode: return '/admin/pembayarans';
                }),
        ];
    }

    // 1. Fungsi Utama (Logika Bisnis) - Private agar tidak bisa dipanggil langsung oleh tombol
private function processPayment()
{
    $nomorBayar = Pembayaran::generateNomorBayar();
    $data = $this->form->getState(); // Validasi berjalan di sini

    $getSiswa = \App\Models\Siswa::select('nama', 'nomor_hp')->findOrFail($data['siswa_id']);
    $siswaNama = $getSiswa->nama;
    $target = $getSiswa->nomor_hp;
    $tanggalPembayaran = Carbon::parse($data['tanggal_pembayaran'])->translatedFormat('d F Y');

    $templatePesan = "
Nomor Bayar: {$nomorBayar} \n
Assalamualaikum Bapak/Ibu, \n
Pembayaran atas nama {$siswaNama} telah kami terima pada tanggal {$tanggalPembayaran}. \n
Rincian Pembayaran: \n";

    $totalBayar = 0;

    DB::beginTransaction(); // Best practice: Gunakan Transaction
    try {
        foreach ($data['Tagihan'] as $bayar) {
            $tagihan = \App\Models\Tagihan::find($bayar['tagihan_id']);
            $bulanNama = \App\Models\Tagihan::BULAN[$tagihan->periode_bulan] ?? '-'; // Handle jika key tidak ada
            $templatePesan .= "- {$tagihan->daftar_biaya} - {$bulanNama} {$tagihan->periode_tahun} : Rp. " . number_format($bayar['jumlah_dibayar'], 0, ",", ".") . "\n";
            $totalBayar += $bayar['jumlah_dibayar'];

            Pembayaran::create([
                'siswa_id' => $data['siswa_id'],
                'user_id' => auth()->user()->id,
                'tagihan_id' => $bayar['tagihan_id'],
                'jumlah_dibayar' => $bayar['jumlah_dibayar'],
                'tanggal_pembayaran' => $data['tanggal_pembayaran'],
                'metode_pembayaran' => $data['metode_pembayaran'],
                'keterangan' => $data['keterangan'] ?? null,
                'bukti_bayar' => $data['bukti_bayar'] ?? null,
                'nomor_bayar' => $nomorBayar,
            ]);
        }

        DB::commit(); // Simpan jika semua loop berhasil

    }
    catch (Halt $exception) {
        \DB::rollBack();
        return;
    } catch (\Exception $e) {
        DB::rollBack(); // Batalkan semua jika ada error

        Notification::make()
            ->danger()
            ->title('Gagal')
            ->body($e->getMessage())
            ->send();

        // Throw error agar function pemanggil tahu kalau ini gagal
        throw $e;
    }

    // --- LOGIKA WHATSAPP ---
    $templatePesan .= "\n Total Pembayaran: Rp. " . number_format($totalBayar, 0, ",", ".") . "\n
Terima kasih atas pembayaran Anda. Alhamdulillah Jazakumullahu Khoiro.";

    $pengaturan = \App\Models\Pengaturan::select('token_whatsapp', 'whatsapp_active')->first();

    if($pengaturan && $pengaturan->whatsapp_active) {
        // ... (Kode Curl Whatsapp Anda Tetap Sama Disini) ...
        // Saya persingkat untuk kejelasan jawaban
        $this->sendWhatsapp($target, $templatePesan, $pengaturan->token_whatsapp);
    }

    Notification::make()
        ->title('Pembayaran Massal Berhasil')
        ->success()
        ->send();
}

// 2. Fungsi untuk Tombol "Buat"
public function create()
{
    try {
        $this->processPayment();
        // Redirect setelah sukses
        $this->redirect('/admin/pembayarans');
    } catch (\Exception $e) {
        // Error sudah dihandle di processPayment, biarkan form tetap terbuka
    }
}

// 3. Fungsi untuk Tombol "Buat & Buat Lainnya"
public function createAnother()
{
    try {
        $this->processPayment();

        // Reset form agar kosong kembali
        $this->form->fill([
            'tanggal_pembayaran' => now(), // Set default value lagi jika perlu
            'metode_pembayaran' => null, // Atau default value
        ]);

        $this->redirect('/admin/multiple-pembayaran');

    } catch (\Exception $e) {
        // Error handling
    }
}

// Helper untuk merapikan kode (Opsional)
private function sendWhatsapp($target, $message, $token) {
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => http_build_query(array(
            'target' => $target,
            'message' => $message,
        )),
        CURLOPT_HTTPHEADER => array(
            "Authorization: $token"
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
}


}
