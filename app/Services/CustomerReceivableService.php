<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Piutang;
use Illuminate\Support\Facades\DB;

class CustomerReceivableService
{
    public function ensureForCustomer(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $customer = Customer::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($customer->stt_arsip || ! $customer->id_kavling || $customer->piutangs()->exists()) {
                return false;
            }

            $unit = $customer->kavling;
            if (! $unit) return false;

            // Preserve the agreed customer price; use the unit price for older records without it.
            $isKpr = strtoupper(trim($customer->jenis_pembelian ?? '')) === 'KPR';
            if ($isKpr && $customer->dp_kpr === null) return false;
            $amount = $isKpr ? (int) $customer->dp_kpr
                : (int) ($customer->total_harga ?: ($unit->total_harga ?: ($unit->hrg_jual + $unit->biaya_surat + $unit->peningkatan_mutu)));
            if ($amount < 0 || (! $isKpr && $amount === 0)) return false;

            Piutang::create([
                'id_customer' => $customer->id,
                'id_bank' => 0,
                'tanggal_piutang' => $customer->tgl_booking ?: ($customer->tanggal_verif ?: now()->toDateString()),
                'deskripsi' => ($isKpr ? 'DP KPR Blok ' : 'Harga Rumah Blok ').$unit->kode_kavling,
                'nominal' => $amount,
                'lampiran' => '',
                'status' => 1,
                'terbayar' => 0,
                'sisa_bayar' => $amount,
                'tgl_pelunasan' => null,
                'id_kategori_transaksi' => 0,
            ]);

            // Include payments recorded before the receivable was created.
            app(PiutangPaymentService::class)->syncCustomer($customer->id);

            return true;
        });
    }
}
