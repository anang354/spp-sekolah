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
        Schema::create('pengaturans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_sekolah')->nullable();
            $table->string('alamat_sekolah')->nullable();
            $table->integer('telepon_sekolah')->nullable();
            $table->string('logo_sekolah')->nullable();
            $table->string('token_whatsapp')->nullable();
            $table->boolean('whatsapp_active')->default(false);
            $table->text('pesan1')->nullable();
            $table->text('pesan2')->nullable();
            $table->text('pesan3')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaturans');
    }
};
