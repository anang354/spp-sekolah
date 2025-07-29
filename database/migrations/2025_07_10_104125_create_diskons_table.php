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
        Schema::create('diskons', function (Blueprint $table) {
            $table->id();
            $table->string('nama_diskon');
            $table->float('persentase')->nullable();
            $table->integer('nominal')->nullable();
            $table->enum('tipe', ['persentase', 'nominal']);
//            $table->enum('berlaku_tagihan', ['sebelum', 'setelah']);
            $table->foreignId('biaya_id')->constrained()->cascadeOnDelete();
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diskons');
    }
};
