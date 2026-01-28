<?php

namespace App\Filament\Actions\Pembayarans;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DownloadPembayaranAction
{

    public static function make() : BulkAction
    {
        return BulkAction::make('download_pembayaran')
            ->icon('heroicon-o-archive-box-arrow-down')
            ->color('primary')
            ->label('Download Pembayaran')
            ->action(function (Collection $records) {
                // Convert records to array to avoid issues when rendering the view
                $path = public_path().'/images/logo-sma.jpg';
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                $image = 'data:image/'.$type.';base64,'.base64_encode($data);
                $recordsArray = $records->map(function ($item) {
                    return $item->toArray();
                })->all();
                $pdf = Pdf::loadView('templates.riwayat-pembayaran', [
                    'records' => $recordsArray,
                    'logo' => $image,
                ]);

                $filename = 'riwayat-pembayaran-' . now()->format('YmdHis') . '.pdf';

                return response()->streamDownload(function () use ($pdf) {
                    echo $pdf->output();
                }, $filename, [
                    'Content-Type' => 'application/pdf',
                ]);
            });
    }
}