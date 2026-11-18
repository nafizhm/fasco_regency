<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_pembelian', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 100)->unique();
            $table->date('tanggal');
            $table->unsignedBigInteger('id_supplier')->index();
            $table->text('keterangan')->nullable();
        });
        Schema::create('pengajuan_pembelian_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pengajuan')->index();
            $table->unsignedBigInteger('id_barang');
            $table->unsignedInteger('jumlah');
            $table->unique(['id_pengajuan', 'id_barang'], 'pengajuan_pembelian_barang_unique');
        });
        Schema::table('barang_masuk', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pengajuan_pembelian')->nullable()->index();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Pengajuan dan penerimaan merupakan riwayat operasional. Gunakan backup untuk rollback.');
    }
};
