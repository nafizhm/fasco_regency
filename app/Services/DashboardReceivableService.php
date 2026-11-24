<?php

namespace App\Services;

use App\Models\Customer;

class DashboardReceivableService
{
    public function summary(): array
    {
        $customers = $this->customers();
        $today = now('Asia/Jakarta')->toDateString();
        $dueToday = $customers->filter(fn ($customer) => $customer->tanggal_jatuh_tempo
            && substr((string) $customer->tanggal_jatuh_tempo, 0, 10) === $today)->count();
        $overdue = $customers->filter(fn ($customer) => $customer->tanggal_jatuh_tempo
            && substr((string) $customer->tanggal_jatuh_tempo, 0, 10) < $today)->count();

        return [
            'piutang' => (int) $customers->sum('sisa_piutang'),
            'piutang_customer' => $customers->count(),
            'jatuh_tempo_hari_ini' => $dueToday,
            'terlambat' => $overdue,
            'notifikasi_piutang' => $dueToday + $overdue,
        ];
    }

    public function customers(array $relations = [], bool $dueOnly = false): \Illuminate\Database\Eloquent\Collection
    {
        $customers = Customer::where('stt_arsip', 0)
            ->with($relations)
            ->withSum(['piutangs as total_tagihan' => fn ($q) => $q->where(fn ($q) => $q->whereNull('jenis_piutang')->orWhere('jenis_piutang', '!=', 'bank'))], 'nominal')
            ->withSum(['pemasukans as total_bayar' => function ($query) {
                $query->whereNotIn('id_kategori_transaksi', \App\Models\KategoriTransaksi::where('kategori', 'Pencairan KPR')->pluck('id'));
                $query->where(fn ($payment) => $payment->whereNull('keterangan')
                    ->orWhere('keterangan', 'NOT LIKE', 'Biaya ganti nama%'));
            }], 'nominal')
            ->get();

        // These two purchases belong to the same person, confirmed by the owner.
        $customers = $customers->groupBy(function ($customer) {
            if (in_array($customer->nama_lengkap, ['SHOFYAN SANTRIANSYAH', 'SHOFYAN'], true)) {
                $customer->loadMissing('kavling');
                if (strtoupper($customer->kavling?->nama_cluster ?? '') === 'KUNING'
                    && in_array((string) $customer->kavling?->kode_kavling, ['17', '18'], true)) {
                    return 'shofyan-kuning-'.$customer->id_lokasi;
                }
            }

            return 'customer-'.$customer->id;
        })->map(function ($members) {
            $customer = $members->sortBy('id')->first();
            if ($members->count() > 1) {
                $customer->nama_lengkap = 'SHOFYAN SANTRIANSYAH';
                $customer->total_tagihan = $members->sum('total_tagihan');
                $customer->total_bayar = $members->sum('total_bayar');
                $customer->gabungan_unit = $members->map(fn ($member) => ($member->kavling?->nama_cluster ?? '-').' / Blok '.($member->kavling?->kode_kavling ?? '-'))->implode(' & ');
                $customer->tanggal_jatuh_tempo = $members->pluck('tanggal_jatuh_tempo')->filter()->sort()->first();
                foreach (['piutangs' => 'tanggal_piutang', 'pemasukans' => 'tanggal'] as $relation => $date) {
                    if ($customer->relationLoaded($relation)) {
                        $customer->setRelation($relation, new \Illuminate\Database\Eloquent\Collection(
                            $members->flatMap(fn ($member) => $member->getRelation($relation))->sortBy($date)->values()->all()
                        ));
                    }
                }
            }

            return $customer;
        });

        return new \Illuminate\Database\Eloquent\Collection($customers
            ->each(function ($customer) {
                $customer->sisa_piutang = max((int) $customer->total_tagihan - (int) $customer->total_bayar, 0);
            })
            ->filter(fn ($customer) => $customer->sisa_piutang > 0)
            ->when($dueOnly, fn ($rows) => $rows->filter(fn ($customer) => $customer->tanggal_jatuh_tempo
                && substr((string) $customer->tanggal_jatuh_tempo, 0, 10) <= now('Asia/Jakarta')->toDateString()))
            ->sortByDesc('sisa_piutang')
            ->values()->all());
    }
}
