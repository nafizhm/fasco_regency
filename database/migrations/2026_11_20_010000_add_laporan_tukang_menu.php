<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $source = DB::table('menu')->where('route_name', 'absen-tukang.index')->first()
                ?? DB::table('menu')->where('route_name', 'tukang.index')->first();
            if (! $source) throw new RuntimeException('Menu Tukang harus tersedia sebelum menambahkan laporan.');
            $menu = DB::table('menu')->where('route_name', 'laporan-tukang.index')->value('id');
            if (! $menu) {
                $menu = DB::table('menu')->insertGetId([
                    'id_parent' => $source->id_parent, 'title' => 'Laporan Tukang',
                    'route_name' => 'laporan-tukang.index', 'icon' => 'fas fa-chart-bar',
                    'urutan' => 3, 'lihat' => 1, 'tambah' => 0, 'edit' => 0, 'hapus' => 0,
                ]);
            }
            foreach (['hak_akses' => 'id_user', 'role_user' => 'id_role'] as $table => $key) {
                if (! Schema::hasTable($table)) continue;
                foreach (DB::table($table)->where('id_menu', $source->id)->get() as $access) {
                    if (DB::table($table)->where($key, $access->$key)->where('id_menu', $menu)->exists()) continue;
                    DB::table($table)->insert([
                        $key => $access->$key, 'id_menu' => $menu, 'lihat' => $access->lihat,
                        'beranda' => 0, 'tambah' => 0, 'edit' => 0, 'hapus' => 0,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        $ids = DB::table('menu')->where('route_name', 'laporan-tukang.index')->pluck('id');
        foreach (['hak_akses', 'role_user'] as $table) {
            if (Schema::hasTable($table)) DB::table($table)->whereIn('id_menu', $ids)->delete();
        }
        DB::table('menu')->whereIn('id', $ids)->delete();
    }
};
