<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class RekapTransaksi
{
    public function build(string $start, string $end, string $mode): array
    {
        // Batas akhir eksklusif mencakup seluruh jam pada tanggal terakhir.
        $until = CarbonImmutable::parse($end)->addDay()->toDateString();
        $income = DB::table('pemasukan as p')->leftJoin('kategori_transaksi as k', 'k.id', '=', 'p.id_kategori_transaksi')
            ->where(fn ($query) => $query->whereNull('p.id_customer')->orWhere('p.id_customer', 0))
            ->where('p.tanggal', '>=', $start)->where('p.tanggal', '<', $until)
            ->selectRaw("p.id, p.tanggal, 'Pemasukan' as sumber, p.no_kwitansi as referensi, COALESCE(k.kategori, 'Tanpa Kategori') as kategori, p.keterangan, p.nominal as pemasukan, 0 as pengeluaran");
        $expense = DB::table('pengeluaran as p')->leftJoin('kategori_transaksi as k', 'k.id', '=', 'p.id_kategori_transaksi')
            ->where('p.tanggal', '>=', $start)->where('p.tanggal', '<', $until)
            ->selectRaw("p.id, p.tanggal, 'Pengeluaran' as sumber, p.no_tanda_terima as referensi, COALESCE(k.kategori, 'Tanpa Kategori') as kategori, p.keterangan, 0 as pemasukan, p.nominal as pengeluaran");
        $union = $income->unionAll($expense);
        $query = DB::query()->fromSub($union, 'transaksi');
        if ($mode === 'ringkasan') {
            $rows = $query->selectRaw('kategori, SUM(pemasukan) as pemasukan, SUM(pengeluaran) as pengeluaran')
                ->groupBy('kategori')->orderBy('kategori')->get();
        } else {
            $rows = $query->orderBy('tanggal')->orderBy('sumber')->orderBy('id')->get();
        }
        return compact('start', 'end', 'mode', 'rows') + [
            'income' => $rows->sum('pemasukan'), 'expense' => $rows->sum('pengeluaran'),
        ];
    }
}
