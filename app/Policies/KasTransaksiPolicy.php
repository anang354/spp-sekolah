<?php

namespace App\Policies;

use App\Models\User;
use App\Models\KasLaporan;
use App\Models\KasTransaksi;
use Illuminate\Auth\Access\Response;

class KasTransaksiPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    protected function isLocked(KasTransaksi $kasTransaksi): bool
    {
        // Ambil tanggal tutup buku PALING TERAKHIR
        $lastClosing = KasLaporan::max('tanggal_tutup');

        if (!$lastClosing) {
            return false; // Belum pernah ada tutup buku, aman.
        }

        // Jika tanggal transaksi LEBIH KECIL atau SAMA DENGAN tanggal tutup buku terakhir
        // Maka transaksi TERKUNCI.
        return $kasTransaksi->tanggal_transaksi <= $lastClosing;
    }
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, KasTransaksi $kasTransaksi): bool
    {
         return $user->isAdmin() || $user->isEditor();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
         return $user->isAdmin() || $user->isEditor();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, KasTransaksi $kasTransaksi): bool
    {
            $hasRole = $user->isAdmin() || $user->isEditor();
            return $hasRole && ! $this->isLocked($kasTransaksi);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, KasTransaksi $kasTransaksi): bool
    {
         $hasRole = $user->isAdmin() || $user->isEditor();
         return $hasRole && ! $this->isLocked($kasTransaksi);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, KasTransaksi $kasTransaksi): bool
    {
            $hasRole = $user->isAdmin() || $user->isEditor();
            return $hasRole && ! $this->isLocked($kasTransaksi);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, KasTransaksi $kasTransaksi): bool
    {
            $hasRole = $user->isAdmin() || $user->isEditor();
            return $hasRole && ! $this->isLocked($kasTransaksi);
    }
}
