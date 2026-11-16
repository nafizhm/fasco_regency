<?php

namespace App\Services;

use App\Models\Customer;

class DashboardReceivableService
{
    public function summary(): array
    {
        $customers = $this->customers();

        return [
            'piutang' => (int) $customers->sum('sisa_piutang'),
            'piutang_customer' => $customers->count(),
        ];
    }

    public function customers(array $relations = []): \Illuminate\Database\Eloquent\Collection
    {
        return Customer::where('stt_arsip', 0)
            ->with($relations)
            ->withSum(['piutangs as total_tagihan' => fn ($q) => $q->where(fn ($q) => $q->whereNull('jenis_piutang')->orWhere('jenis_piutang', '!=', 'bank'))], 'nominal')
            ->withSum(['pemasukans as total_bayar' => function ($query) {
                $query->whereNotIn('id_kategori_transaksi', \App\Models\KategoriTransaksi::where('kategori', 'Pencairan KPR')->pluck('id'));
                $query->where(fn ($payment) => $payment->whereNull('keterangan')
                    ->orWhere('keterangan', 'NOT LIKE', 'Biaya ganti nama%'));
            }], 'nominal')
            ->get()
            ->each(function ($customer) {
                $customer->sisa_piutang = max((int) $customer->total_tagihan - (int) $customer->total_bayar, 0);
            })
            ->filter(fn ($customer) => $customer->sisa_piutang > 0)
            ->sortByDesc('sisa_piutang')
            ->values();
    }
}
