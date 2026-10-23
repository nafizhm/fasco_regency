<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $plans = array (
  '' => 
  array (
    0 => 'Beranda',
    1 => 'Siteplan',
    2 => 'Transaksi',
    3 => 'Customer',
    4 => 'Keuangan',
    5 => 'Progres Pembangunan',
    6 => 'Legal',
    7 => 'Cetak Berkas',
    8 => 'Mutasi Transaksi',
    9 => 'Pembelian',
    10 => 'Laporan',
    11 => 'Dokumentasi Konten',
    12 => 'Master Data',
    13 => 'Pengaturan',
  ),
  'Siteplan' => 
  array (
    0 => 'siteplan-penjualan.index',
    1 => 'siteplan-listrik.index',
    2 => 'siteplan-air.index',
    3 => 'st-bphtb-ssp.index',
    4 => 'st-balik-nama.index',
  ),
  'Transaksi' => 
  array (
    0 => 'pengajuan-hold.index',
    1 => 'wawancara.index',
    2 => 'acc-bank.index',
    3 => 'akad.index',
  ),
  'Customer' => 
  array (
    0 => 'customer.index',
    1 => 'upload-file.index',
    2 => 'arsip-customer.index',
  ),
  'Keuangan' => 
  array (
    0 => 'pembayaran.index',
    1 => 'piutang.index',
    2 => 'pemasukan.index',
    3 => 'hutang.index',
    4 => 'pengeluaran.index',
    5 => 'mutasi-saldo.index',
    6 => 'keuangan-retensi.index',
    7 => 'laporan-arus-kas.index',
    8 => 'kategori-transaksi.index',
  ),
  'Legal' => 
  array (
    0 => 'pengajuan-berkas.index',
    1 => 'bphtb-ssp.index',
    2 => 'balik-nama.index',
    3 => 'listrik-air.index',
  ),
  'Cetak Berkas' => 
  array (
    0 => 'sppr.index',
    1 => 'bast.index',
  ),
  'Mutasi Transaksi' => 
  array (
    0 => 'pindah-unit.index',
    1 => 'ganti-nama.index',
    2 => 'pembelian-cancel.index',
  ),
  'Master Data' => 
  array (
    0 => 'perusahaan.index',
    1 => 'lokasi-kavling.index',
    2 => 'kavling.index',
    3 => 'marketing-offline.index',
    4 => 'bank-transaksi.index',
    5 => 'bank-kpr.index',
    6 => 'notaris.index',
    7 => 'jenis-berkas.index',
    8 => 'retensi.index',
  ),
  'Pengaturan' => 
  array (
    0 => 'pengaturan-profil.index',
    1 => 'pengaturan-media.index',
    2 => 'pengaturan-pengguna.index',
    3 => 'role-user.index',
    4 => 'hak-akses.index',
    5 => 'list-penjualan.index',
    6 => 'log-aktivitas.index',
  ),
);
        DB::transaction(function () use ($plans) {
            foreach ($plans as $parentTitle => $items) {
                $parentId = $parentTitle === '' ? 0 : DB::table('menu')->where('id_parent', 0)->where('title', $parentTitle)->value('id');
                if ($parentId === null) continue;
                foreach ($items as $index => $key) {
                    DB::table('menu')->where('id_parent', $parentId)
                        ->where($parentTitle === '' ? 'title' : 'route_name', $key)
                        ->update(['urutan' => ($index + 1) * 10]);
                }
            }
        });
    }

    public function down(): void
    {
        $previous = array (
  3 => 2,
  5 => 1,
  6 => 0,
  7 => 6,
  8 => 8,
  13 => 13,
  14 => 14,
  15 => 16,
  17 => 18,
  19 => 20,
  20 => 1,
  23 => 3,
  24 => 4,
  25 => 6,
  26 => 1,
  27 => 3,
  28 => 2,
  29 => 1,
  31 => 3,
  32 => 4,
  35 => 0,
  45 => 2,
  46 => 1,
  47 => 1,
  48 => 2,
  49 => 3,
  50 => 4,
  51 => 5,
  52 => 6,
  53 => 7,
  56 => 1,
  57 => 2,
  61 => 6,
  64 => 1,
  65 => 99,
  66 => 2,
  67 => 3,
  69 => 5,
  70 => 3,
  72 => 0,
  73 => 7,
  75 => 9,
  77 => 6,
  78 => 4,
  79 => 5,
  80 => 4,
  82 => 3,
  84 => 5,
  85 => 7,
  86 => 1,
  87 => 10,
  88 => 8,
  90 => 2,
  91 => 0,
  92 => 7,
  93 => 100,
  94 => 21,
  95 => 15,
  96 => 101,
  97 => 102,
);
        DB::transaction(function () use ($previous) {
            foreach ($previous as $id => $order) {
                DB::table('menu')->where('id', $id)->update(['urutan' => $order]);
            }
        });
    }
};