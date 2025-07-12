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
        Schema::create('biayas', function (Blueprint $table) {
            $table->id();
            $table->string('nama_biaya');
            $table->integer('nominal');
            $table->enum('jenis_siswa',['boarding', 'non-boarding', 'semua']);
            $table->string('keterangan')->nullable();
            $table->enum('jenjang', ['smp', 'sma']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biayas');
    }
};
