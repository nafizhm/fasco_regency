<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer', fn (Blueprint $table) => $table->text('catatan_kwitansi')->nullable());
        $profileMenu = DB::table('menu')->where('route_name', 'pengaturan-profil.index')->first();
        $parent = $profileMenu?->id_parent ?: DB::table('menu')->where('title', 'Pengaturan')->value('id');
        if (! $parent) throw new RuntimeException('Menu Pengaturan tidak ditemukan.');
        $id = DB::table('menu')->insertGetId([
            'id_parent' => $parent, 'title' => 'Catatan Kwitansi Customer', 'route_name' => 'customer-receipt-notes.index',
            'icon' => 'fas fa-file-alt', 'urutan' => (int) DB::table('menu')->where('id_parent', $parent)->max('urutan') + 1,
            'lihat' => 1, 'tambah' => 0, 'edit' => 1, 'hapus' => 0,
        ]);
        if ($profileMenu) {
            foreach (DB::table('hak_akses')->where('id_menu', $profileMenu->id)->get() as $access) {
                DB::table('hak_akses')->insert([
                    'id_user' => $access->id_user, 'id_menu' => $id, 'lihat' => $access->lihat,
                    'beranda' => 0, 'tambah' => 0, 'edit' => $access->edit, 'hapus' => 0,
                ]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('menu')->where('route_name', 'customer-receipt-notes.index')->pluck('id');
        DB::table('hak_akses')->whereIn('id_menu', $ids)->delete();
        DB::table('menu')->whereIn('id', $ids)->delete();
        Schema::table('customer', fn (Blueprint $table) => $table->dropColumn('catatan_kwitansi'));
    }
};
