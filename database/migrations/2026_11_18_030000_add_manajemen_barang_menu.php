<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $source = DB::table('menu')->where('route_name', 'barang-masuk.index')->value('id');
            $children = DB::table('menu')->whereIn('route_name', ['barang-masuk.index', 'barang-keluar.index'])->pluck('id');
            $position = DB::table('menu')->whereIn('id', $children)->min('urutan') ?? 20;
            $parent = DB::table('menu')->where('title', 'Manajemen Barang')->where('id_parent', 0)->value('id');
            if (! $parent) {
                $parent = DB::table('menu')->insertGetId([
                    'id_parent' => 0, 'title' => 'Manajemen Barang', 'route_name' => '#',
                    'icon' => 'fas fa-boxes', 'urutan' => $position,
                    'lihat' => 1, 'tambah' => 0, 'edit' => 0, 'hapus' => 0,
                ]);
            }
            $values = ['id_parent' => $parent, 'title' => 'Pengajuan Pembelian',
                'route_name' => 'pengajuan-pembelian.index', 'icon' => 'fas fa-clipboard-list',
                'urutan' => 1, 'lihat' => 1, 'tambah' => 1, 'edit' => 1, 'hapus' => 1];
            $purchase = DB::table('menu')->where('route_name', $values['route_name'])->value('id');
            if ($purchase) {
                DB::table('menu')->where('id', $purchase)->update($values);
            } else {
                $purchase = DB::table('menu')->insertGetId($values);
            }
            foreach (['barang-masuk.index' => 2, 'barang-keluar.index' => 3] as $route => $order) {
                DB::table('menu')->where('route_name', $route)->update(['id_parent' => $parent, 'urutan' => $order]);
            }
            foreach (['hak_akses' => 'id_user', 'role_user' => 'id_role'] as $table => $ownerKey) {
                if (! Schema::hasTable($table)) {
                    continue;
                }
                foreach (DB::table($table)->where('id_menu', $source)->get() as $access) {
                    $key = [$ownerKey => $access->$ownerKey, 'id_menu' => $purchase];
                    if (! DB::table($table)->where($key)->exists()) {
                        DB::table($table)->insert($key + ['lihat' => $access->lihat, 'beranda' => 0,
                            'tambah' => $access->tambah, 'edit' => $access->edit, 'hapus' => $access->hapus]);
                    }
                }
                $visible = DB::table($table)->whereIn('id_menu', $children->push($purchase)->unique())
                    ->where('lihat', 1)->distinct()->pluck($ownerKey);
                foreach ($visible as $owner) {
                    DB::table($table)->updateOrInsert([$ownerKey => $owner, 'id_menu' => $parent],
                        ['lihat' => 1, 'beranda' => 0, 'tambah' => 0, 'edit' => 0, 'hapus' => 0]);
                }
            }
        });
    }

    public function down(): void
    {
        // Keep menu access and operational records intact.
    }
};
