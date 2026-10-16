<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $parent = DB::table('menu')->where('title', 'Cetak Berkas')->value('id');
            if (!$parent) {
                $parent = DB::table('menu')->insertGetId([
                    'id_parent' => 0, 'title' => 'Cetak Berkas', 'route_name' => '',
                    'icon' => 'fa-print', 'urutan' => (int) DB::table('menu')->where('id_parent', 0)->max('urutan') + 1,
                    'lihat' => 1, 'tambah' => 0, 'edit' => 0, 'hapus' => 0,
                ]);
            }
            foreach (['sppr.index' => 1, 'bast.index' => 2] as $route => $order) {
                DB::table('menu')->where('route_name', $route)->update(['id_parent' => $parent, 'urutan' => $order]);
            }
            DB::table('menu')->where('route_name', 'wawancara.index')->update(['title' => 'Proses Bank']);
            DB::table('menu')->where('route_name', 'acc-bank.index')->update(['title' => 'SP3K']);
            $children = DB::table('menu')->where('id_parent', $parent)->pluck('id');
            $users = DB::table('hak_akses')->whereIn('id_menu', $children)->where('lihat', 1)->distinct()->pluck('id_user');
            foreach ($users as $id) {
                DB::table('hak_akses')->updateOrInsert(['id_user' => $id, 'id_menu' => $parent],
                    ['lihat' => 1, 'beranda' => 0, 'tambah' => 0, 'edit' => 0, 'hapus' => 0]);
            }
        });
    }

    public function down(): void
    {
        $parent = DB::table('menu')->where('title', 'Transaksi')->value('id');
        if ($parent) DB::table('menu')->whereIn('route_name', ['sppr.index', 'bast.index'])->update(['id_parent' => $parent]);
        DB::table('menu')->where('route_name', 'wawancara.index')->update(['title' => 'Wawancara']);
        DB::table('menu')->where('route_name', 'acc-bank.index')->update(['title' => 'ACC Bank']);
    }
};
