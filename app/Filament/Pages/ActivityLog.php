<?php

namespace App\Filament\Pages;

use Filament\Tables\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLog extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';

    protected static string $view = 'filament.pages.activity-log';

    protected  static ?string $title = 'Log Aktifitas';

    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 100;

    public function __canAccess(): bool
    {
        return auth()->user()->role === 'admin';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Activity::query()->latest()
            )
            ->columns([
                TextColumn::make('causer.name')
                ->label('User')
                ->searchable(),
                TextColumn::make('description'),
                TextColumn::make('subject_id'),
                TextColumn::make('subject_type'),
                TextColumn::make('event'),
                TextColumn::make('created_at')
                    ->label('waktu')
                    ->since(),
            ])
            ->actions([
                Action::make('lihat_log')
                ->label('Lihat Log')
                    ->url(fn ($record) => $this->getSubjectDetailUrl($record))
                ->openUrlInNewTab(),
            ]);
    }

    protected function getSubjectDetailUrl($record): ?string
    {
        $subject = $record->subject;

        if (! $subject) {
            return null;
        }

        // Ambil nama resource (misalnya siswa -> siswas)
        $resource = match (get_class($subject)) {
            \App\Models\Siswa::class => 'siswas',
            \App\Models\Tagihan::class => 'tagihans',
            \App\Models\Pembayaran::class => 'pembayarans',
            \App\Models\User::class => 'users',
            \App\Models\Biaya::class => 'biayas',
            // Tambah model lain di sini sesuai kebutuhan
            default => null,
        };

        return $resource
            ? route("filament.admin.resources.{$resource}.activities", ['record' => $subject->getKey()])
            : null;
    }
}
