<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemasukan', function (Blueprint $table) {
            $table->unsignedBigInteger('id_sp3k')->nullable();
            $table->string('no_sp3k')->nullable();
            $table->unsignedBigInteger('plafon_sp3k')->nullable();
            $table->unsignedBigInteger('id_bank_kpr_sp3k')->nullable();
        });
        // Legacy disbursements retain their actual gross amount. Their original SP3K is unknown.
        $ids = DB::table('kategori_transaksi')->where('kategori', 'Pencairan KPR')->pluck('id');
        foreach (DB::table('pemasukan')->whereIn('id_kategori_transaksi', $ids)->get() as $payment) {
            $retensi = DB::table('pemasukan_retensi')->where('id_pemasukan', $payment->id)->sum('nominal');
            DB::table('pemasukan')->where('id', $payment->id)->update([
                'plafon_sp3k' => max((int) $payment->nominal + (int) $retensi, 0),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('pemasukan', fn (Blueprint $table) => $table->dropColumn([
            'id_sp3k', 'no_sp3k', 'plafon_sp3k', 'id_bank_kpr_sp3k',
        ]));
    }
};
