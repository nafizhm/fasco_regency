<?php

namespace App\Services;

use App\Models\{Customer, KategoriTransaksi, WawancaraSp3k};
use Illuminate\Support\Facades\DB;

class KprReceivableReport
{
    public function rows(): \Illuminate\Support\Collection
    {
        $bankCategories = KategoriTransaksi::where('kategori', 'Pencairan KPR')->pluck('id');
        $customers = Customer::where('stt_arsip', 0)->whereRaw('UPPER(TRIM(jenis_pembelian)) = ?', ['KPR'])
            ->with(['lokasi', 'kavling', 'bankKPR', 'pemasukans' => fn ($q) => $q
                ->whereIn('id_kategori_transaksi', $bankCategories)->orderBy('tanggal')->orderBy('id')])->get();
        $ids = $customers->pluck('id');
        $sp3ks = WawancaraSp3k::with(['wawancara', 'bankKPR'])
            ->whereHas('wawancara', fn ($q) => $q->whereIn('id_customer', $ids))
            ->orderByDesc('tgl_terbit_sp3k')->orderByDesc('id')->get()
            ->unique(fn ($sp3k) => $sp3k->wawancara->id_customer)
            ->keyBy(fn ($sp3k) => $sp3k->wawancara->id_customer);
        $akad = DB::table('detail_akad')->join('akad', 'akad.id', '=', 'detail_akad.id_akad')
            ->whereIn('detail_akad.id_customer', $ids)->where('detail_akad.status', 2)
            ->whereDate('akad.tgl_akad', '<=', now('Asia/Jakarta')->toDateString())
            ->select('detail_akad.id_customer', DB::raw('MIN(akad.tgl_akad) as tanggal'))
            ->groupBy('detail_akad.id_customer')->pluck('tanggal', 'id_customer');

        return $customers->map(function ($customer) use ($sp3ks, $akad) {
            $sp3k = $sp3ks->get($customer->id);
            $snapshot = $customer->pemasukans->sortByDesc('id')->first(fn ($p) => $p->plafon_sp3k !== null);
            $plafon = $sp3k ? (int) $sp3k->acc_plafon : ($snapshot ? (int) $snapshot->plafon_sp3k : null);
            return ['customer' => $customer, 'akad' => $akad[$customer->id] ?? null,
                'plafon' => $plafon, 'payments' => $customer->pemasukans,
                'sisa' => $plafon === null ? null : max($plafon - $customer->pemasukans->sum('nominal'), 0),
                'bank' => $sp3k?->bankKPR?->nama ?? $customer->bankKPR?->nama ?? '-',
            ];
        })->sort(function ($left, $right) {
            $leftUnit = $left['customer']->kavling;
            $rightUnit = $right['customer']->kavling;
            $cluster = strnatcasecmp(trim($leftUnit?->nama_cluster ?? ''), trim($rightUnit?->nama_cluster ?? ''));
            return $cluster ?: strnatcasecmp($leftUnit?->kode_kavling ?? '', $rightUnit?->kode_kavling ?? '')
                ?: ($left['customer']->id <=> $right['customer']->id);
        })->values();
    }
}
