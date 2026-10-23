<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        foreach ([['Pembayaran Piutang', 'PEMASUKAN', 'BAYAR-PIUTANG'], ['Pembayaran Hutang', 'PENGELUARAN', 'BAYAR-HUTANG']] as [$name, $type, $code]) {
            if (!DB::table('kategori_transaksi')->where('kategori', $name)->where('jenis_kategori', $type)->exists()) {
                DB::table('kategori_transaksi')->insert(['kategori' => $name, 'jenis_kategori' => $type, 'kode' => $code, 'stt_fix' => 1]);
            }
        }
    }

    public function down(): void
    {
        // Retain categories that may already be referenced by payments.
    }
};
