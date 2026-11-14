<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerKprDp
{
    public function sync(int $id, int $amount): void
    {
        DB::transaction(function () use ($id, $amount) {
            $customer = Customer::whereKey($id)->lockForUpdate()->firstOrFail();
            if (strtoupper(trim($customer->jenis_pembelian ?? '')) !== 'KPR' || $customer->stt_arsip) {
                throw ValidationException::withMessages(['dp_kpr' => 'DP hanya dapat diubah untuk customer KPR aktif.']);
            }
            if ($amount < 0 || $amount > (int) $customer->total_harga) {
                throw ValidationException::withMessages(['dp_kpr' => 'DP harus antara Rp0 dan total harga rumah.']);
            }
            $bills = $customer->piutangs()->lockForUpdate()->get();
            $targets = $bills->filter(fn ($bill) => str_starts_with(mb_strtolower(trim($bill->deskripsi ?? '')), 'dp kpr')
                || str_contains(mb_strtolower($bill->deskripsi ?? ''), 'harga rumah'));
            if ($targets->count() > 1 || ($bills->isNotEmpty() && $targets->isEmpty())) {
                throw ValidationException::withMessages(['dp_kpr' => 'Tagihan utama tidak tunggal atau belum dapat dikenali. Periksa rincian tagihan sebelum mengoreksi DP agar pembayaran lama tetap sesuai.']);
            }
            $customer->update(['dp_kpr' => $amount]);
            if ($customer->id_booking) {
                DB::table('pengajuan_hold')->where('id', $customer->id_booking)->update(['dp_kpr' => $amount]);
            }
            if ($targets->isNotEmpty()) {
                $bill = $targets->first();
                // Keep the original bill ID so explicitly linked historic payments are preserved.
                DB::table('piutang')->where('id', $bill->id)->update([
                    'nominal' => $amount, 'deskripsi' => 'DP KPR Blok '.($customer->kavling?->kode_kavling ?? '-'),
                ]);
            } else {
                app(CustomerReceivableService::class)->ensureForCustomer($id);
            }
            app(PiutangPaymentService::class)->syncCustomer($id);
        });
    }
}
