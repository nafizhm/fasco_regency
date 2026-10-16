<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\KategoriTransaksi;
use App\Models\Pemasukan;
use App\Models\PemasukanRetensi;
use App\Models\Retensi;
use Illuminate\Support\Facades\DB;

class RetensiController extends Controller
{
    public function index()
    {
        $retensis = Retensi::orderBy('id')->get();
        $pencairanKategoriIds = KategoriTransaksi::where('kategori', 'Pencairan KPR')->pluck('id');

        $customerRows = Customer::query()
            ->select([
                'customer.id',
                'customer.nama_lengkap',
                'lokasi_kavling.nama_kavling',
                'kavling_peta.kode_kavling',
            ])
            ->join('pemasukan', function ($join) use ($pencairanKategoriIds) {
                $join->on('pemasukan.id_customer', '=', 'customer.id')
                    ->whereIn('pemasukan.id_kategori_transaksi', $pencairanKategoriIds);
            })
            ->leftJoin('lokasi_kavling', 'lokasi_kavling.id', '=', 'customer.id_lokasi')
            ->leftJoin('kavling_peta', 'kavling_peta.id', '=', 'customer.id_kavling')
            ->groupBy(
                'customer.id',
                'customer.nama_lengkap',
                'lokasi_kavling.nama_kavling',
                'kavling_peta.kode_kavling'
            )
            ->orderBy('lokasi_kavling.nama_kavling')
            ->orderBy('kavling_peta.kode_kavling')
            ->get();

        $customerIds = $customerRows->pluck('id');
        $plafonMap = Pemasukan::whereIn('id_customer', $customerIds)
            ->whereIn('id_kategori_transaksi', $pencairanKategoriIds)
            ->whereNotNull('plafon_sp3k')
            ->orderByDesc('id')->get()->unique('id_customer')->pluck('plafon_sp3k', 'id_customer');

        $pencairanMap = Pemasukan::query()
            ->select('id_customer', DB::raw('SUM(nominal) as total_pencairan'))
            ->whereIn('id_customer', $customerIds)
            ->whereIn('id_kategori_transaksi', $pencairanKategoriIds)
            ->groupBy('id_customer')
            ->pluck('total_pencairan', 'id_customer');

        $latestPaymentIds = Pemasukan::whereIn('id_customer', $customerIds)
            ->whereIn('id_kategori_transaksi', $pencairanKategoriIds)
            ->select(DB::raw('MAX(id) as id'))->groupBy('id_customer')->pluck('id');

        $retensiRows = PemasukanRetensi::query()
            ->select('pemasukan.id_customer', 'pemasukan_retensi.id_retensi', DB::raw('SUM(pemasukan_retensi.nominal) as total_nominal'))
            ->join('pemasukan', 'pemasukan.id', '=', 'pemasukan_retensi.id_pemasukan')
            ->whereIn('pemasukan.id', $latestPaymentIds)
            ->whereIn('pemasukan.id_customer', $customerIds)
            ->whereIn('pemasukan.id_kategori_transaksi', $pencairanKategoriIds)
            ->groupBy('pemasukan.id_customer', 'pemasukan_retensi.id_retensi')
            ->get();

        $retensiMap = [];
        foreach ($retensiRows as $row) {
            $retensiMap[$row->id_customer][$row->id_retensi] = (int) $row->total_nominal;
        }

        $rows = $customerRows->map(function ($customer) use ($retensis, $pencairanMap, $retensiMap, $plafonMap) {
            $retensiPerCustomer = $retensiMap[$customer->id] ?? [];
            $totalRetensi = collect($retensiPerCustomer)->sum();

            return [
                'id' => $customer->id,
                'lokasi_unit' => trim(($customer->nama_kavling ?? '-') . ' + ' . ($customer->kode_kavling ?? '-')),
                'plafon' => (int) ($plafonMap[$customer->id] ?? (($pencairanMap[$customer->id] ?? 0) + $totalRetensi)),
                'pencairan' => (int) ($pencairanMap[$customer->id] ?? 0),
                'retensi' => collect($retensis)->mapWithKeys(function ($retensi) use ($retensiPerCustomer) {
                    return [$retensi->id => (int) ($retensiPerCustomer[$retensi->id] ?? 0)];
                })->toArray(),
                'total_retensi' => (int) $totalRetensi,
            ];
        });

        $totals = [
            'plafon' => $rows->sum('plafon'),
            'pencairan' => $rows->sum('pencairan'),
            'retensi' => [],
            'total_retensi' => $rows->sum('total_retensi'),
        ];

        foreach ($retensis as $retensi) {
            $totals['retensi'][$retensi->id] = $rows->sum(function ($row) use ($retensi) {
                return $row['retensi'][$retensi->id] ?? 0;
            });
        }

        return view('admin.keuangan.retensi.index', compact('retensis', 'rows', 'totals'));
    }
}
