<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tagihans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained()->onDelete('cascade');
            $table->integer('periode_bulan');
            $table->integer('periode_tahun');
            $table->date('jatuh_tempo');
            $table->integer('jumlah_tagihan');
            $table->integer('jumlah_diskon');
            $table->string('daftar_biaya');
            $table->string('daftar_diskon');
            $table->integer('jumlah_netto');
            $table->string('status')->default('baru');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihans');
    }
};
