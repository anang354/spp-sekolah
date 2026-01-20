<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLog extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-information-circle';

    protected static string $view = 'filament.pages.activity-log';

    protected  static ?string $title = 'Log Aktifitas';

    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 100;

    public function __canAccess(): bool
    {
        return auth()->user()->role === 'admin' || auth()->user()->role === 'editor';
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
                TextColumn::make('properties')
                    ->limit(100)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('waktu')
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('subject_type')
                    ->label('Filter Model')
                    ->options(
                        Activity::query()
                            ->distinct()
                            ->pluck('subject_type', 'subject_type')
                    )
                    ->searchable()
                    ->placeholder('Semua Model'),
                SelectFilter::make('event')
                ->label('Filter Event')
                ->options([
                    'created' => 'created',
                    'updated' => 'updated',
                    'deleted' => 'deleted',
                ])
            ])
            ->actions([
                Action::make('lihat_log')
                ->label('Lihat Log')
                    ->url(fn ($record) => $this->getSubjectDetailUrl($record))
                ->openUrlInNewTab(),
            ])
            ->headerActions([
                Action::make('delete')
                    ->visible(fn () => auth()->user()->role === 'admin')
                    ->label('Hapus Log')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->modalHeading('Hapus Log Aktivitas')
                    ->modalIcon('heroicon-o-exclamation-triangle')
                    ->modalIconColor('danger')
                    ->modalAlignment('center')
                    ->modalSubmitActionLabel('Ya Hapus')
                    ->modalDescription('Apakah anda yakin ingin menghapus log aktivitas? Tindakan ini tidak dapat dibatalkan')
                    ->action(function() {
                        Activity::truncate();
                        Notification::make('success')
                            ->success()->title('Log Aktivitas berhasil dihapus')->send();
                    }),
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
            \App\Models\Alumni::class => 'alumnis',
            \App\Models\PembayaranAlumni::class => 'pembayaran-alumnis',
            // Tambah model lain di sini sesuai kebutuhan
            default => null,
        };

        return $resource
            ? route("filament.admin.resources.{$resource}.activities", ['record' => $subject->getKey()])
            : null;
    }
}
