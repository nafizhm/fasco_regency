<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KprReceivableService
{
    public function sync(int $id): void
    {
        if (!Schema::hasColumn('piutang', 'jenis_piutang')) return;
        $customer = Customer::whereKey($id)->lockForUpdate()->first();
        if (!$customer || strtoupper(trim($customer->jenis_pembelian ?? '')) !== 'KPR' || $customer->stt_arsip) return;

        $sp3k = Schema::hasTable('wawancara_sp3k') && Schema::hasTable('wawancara')
            ? app(Sp3kPlafonService::class)->latestForCustomer($id) : null;
        $plafon = $sp3k ? (int) $sp3k->acc_plafon : null;
        $dp = $plafon !== null ? max((int) $customer->total_harga - $plafon, 0) : $customer->dp_kpr;
        $bills = DB::table('piutang')->where('id_customer', $id)->orderBy('id')->get();
        foreach (['dp' => $dp, 'bank' => $plafon] as $type => $amount) {
            if ($amount === null) continue;
            $matches = $bills->filter(function ($bill) use ($type) {
                if ($bill->jenis_piutang) return $bill->jenis_piutang === $type;
                $description = mb_strtolower(trim($bill->deskripsi ?? ''));
                return $type === 'dp' && (str_starts_with($description, 'dp kpr') || str_contains($description, 'harga rumah'));
            });
            // Keep IDs and all historic payments; ambiguous legacy bills require review.
            if ($matches->count() > 1) continue;
            $values = ['jenis_piutang' => $type, 'nominal' => (int) $amount,
                'deskripsi' => $type === 'bank' ? 'Piutang Bank KPR' : 'DP KPR Blok '.($customer->kavling?->kode_kavling ?? '-')];
            if ($matches->isNotEmpty()) {
                DB::table('piutang')->where('id', $matches->first()->id)->update($values);
            } else {
                DB::table('piutang')->insert($values + ['id_customer' => $id, 'id_bank' => 0,
                    'tanggal_piutang' => $customer->tgl_booking ?: now()->toDateString(),
                    'lampiran' => '', 'status' => 1, 'terbayar' => 0, 'sisa_bayar' => (int) $amount,
                    'id_kategori_transaksi' => 0]);
            }
        }
        if ($dp !== null) DB::table('customer')->where('id', $id)->update(['dp_kpr' => $dp]);
    }
}
