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
            ->where('p.tanggal', '>=', $start)->where('p.tanggal', '<', $until)
            ->selectRaw("p.id, p.tanggal, 'Pemasukan' as sumber, p.no_kwitansi as referensi, COALESCE(k.kategori, 'Tanpa Kategori') as kategori, p.keterangan, p.nominal as pemasukan, 0 as pengeluaran");
        $expense = DB::table('pengeluaran as p')->leftJoin('kategori_transaksi as k', 'k.id', '=', 'p.id_kategori_transaksi')
            ->where('p.tanggal', '>=', $start)->where('p.tanggal', '<', $until)
            ->selectRaw("p.id, p.tanggal, 'Pengeluaran' as sumber, p.no_tanda_terima as referensi, COALESCE(k.kategori, 'Tanpa Kategori') as kategori, p.keterangan, 0 as pemasukan, p.nominal as pengeluaran");
        $goods = DB::table('barang_masuk_pembayaran as p')
            ->where('p.tanggal_pembayaran', '>=', $start)->where('p.tanggal_pembayaran', '<', $until)
            ->selectRaw("p.id, p.tanggal_pembayaran as tanggal, 'Barang Masuk' as sumber, p.id_barang_masuk as referensi, 'Pembayaran Barang Masuk' as kategori, p.keterangan, 0 as pemasukan, p.jumlah_bayar as pengeluaran");
        $spk = DB::table('spk_pembayaran as p')->leftJoin('spk as s', 's.id', '=', 'p.id_spk')
            ->where('p.tanggal', '>=', $start)->where('p.tanggal', '<', $until)
            ->selectRaw("p.id, p.tanggal, 'SPK' as sumber, s.nomor as referensi, 'Pembayaran SPK' as kategori, p.keterangan, 0 as pemasukan, p.nominal as pengeluaran");
        $union = $income->unionAll($expense)->unionAll($goods)->unionAll($spk);
        $query = DB::query()->fromSub($union, 'transaksi');
        if ($mode === 'ringkasan') {
            $rows = $query->selectRaw('kategori, SUM(pemasukan) as pemasukan, SUM(pengeluaran) as pengeluaran')
                ->groupBy('kategori')->orderBy('kategori')->get();
        } else {
            $rows = $query->orderBy('tanggal')->orderBy('sumber')->orderBy('id')->get();
            foreach ($rows as $row) {
                if ($row->sumber === 'Barang Masuk') $row->referensi = 'BM-'.str_pad((string) $row->referensi, 5, '0', STR_PAD_LEFT);
            }
        }
        return compact('start', 'end', 'mode', 'rows') + [
            'income' => $rows->sum('pemasukan'), 'expense' => $rows->sum('pengeluaran'),
        ];
    }
}
