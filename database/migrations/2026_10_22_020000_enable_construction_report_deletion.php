<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $ids = DB::table('menu')->where('route_name', 'construction.index')->pluck('id');
        DB::table('menu')->whereIn('id', $ids)->update(['hapus' => 1]);
        foreach (['hak_akses', 'role_user'] as $table) {
            DB::table($table)->whereIn('id_menu', $ids)->where('lihat', 1)->where('tambah', 1)->update(['hapus' => 1]);
        }
    }

    public function down(): void
    {
        $ids = DB::table('menu')->where('route_name', 'construction.index')->pluck('id');
        foreach (['menu', 'hak_akses', 'role_user'] as $table) {
            DB::table($table)->whereIn($table === 'menu' ? 'id' : 'id_menu', $ids)->update(['hapus' => 0]);
        }
    }
};
