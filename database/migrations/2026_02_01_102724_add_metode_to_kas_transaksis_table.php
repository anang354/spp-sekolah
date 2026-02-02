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
        Schema::table('kas_transaksis', function (Blueprint $table) {
            $table->enum('metode', ['tunai', 'non-tunai'])->default('tunai')->after('jenis_transaksi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kas_transaksis', function (Blueprint $table) {
            $table->dropColumn('metode');
        });
    }
};
