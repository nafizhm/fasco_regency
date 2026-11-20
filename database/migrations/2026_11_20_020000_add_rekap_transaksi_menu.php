<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $source = DB::table('menu')->where('route_name', 'spk.index')->first();
            if (! $source) throw new RuntimeException('Menu SPK harus tersedia sebelum Rekap Transaksi.');
            $menu = DB::table('menu')->where('route_name', 'rekap-transaksi.index')->value('id');
            if (! $menu) {
                DB::table('menu')->where('id_parent', $source->id_parent)->where('urutan', '>', $source->urutan)->increment('urutan');
                $menu = DB::table('menu')->insertGetId([
                    'id_parent' => $source->id_parent, 'title' => 'Rekap Transaksi', 'route_name' => 'rekap-transaksi.index',
                    'icon' => 'fas fa-chart-bar', 'urutan' => $source->urutan + 1,
                    'lihat' => 1, 'tambah' => 0, 'edit' => 0, 'hapus' => 0,
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
        $ids = DB::table('menu')->where('route_name', 'rekap-transaksi.index')->pluck('id');
        foreach (['hak_akses', 'role_user'] as $table) {
            if (Schema::hasTable($table)) DB::table($table)->whereIn('id_menu', $ids)->delete();
        }
        DB::table('menu')->whereIn('id', $ids)->delete();
    }
};
