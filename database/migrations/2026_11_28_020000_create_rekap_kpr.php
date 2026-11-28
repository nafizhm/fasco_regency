<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rekap_kpr')) {
            Schema::create('rekap_kpr', function (Blueprint $t) {
                $t->id();
                $t->string('bank', 100)->index();
                $t->string('nama_user');
                $t->string('blok', 100)->nullable();
                $t->date('tanggal_booking')->nullable();
                $t->date('tanggal_itj')->nullable();
                $t->date('tanggal_masuk')->nullable();
                $t->text('keterangan')->nullable();
                $t->string('perumahan', 150);
                $t->boolean('tandai_merah')->default(false);
                $t->timestamps();
            });
        }
        $menu = DB::table('menu')->where('route_name', 'rekap-kpr.index')->value('id');
        if (! $menu) {
            $menu = DB::table('menu')->insertGetId([
                'id_parent' => 0, 'title' => 'Rekap KPR', 'route_name' => 'rekap-kpr.index', 'icon' => 'fas fa-list-alt',
                'urutan' => (DB::table('menu')->max('urutan') ?? 0) + 1, 'lihat' => 1, 'tambah' => 1, 'edit' => 1, 'hapus' => 1,
            ]);
        }
        foreach (DB::table('users')->whereRaw('LOWER(username) = ?', ['tutut'])->pluck('id') as $user) {
            DB::table('hak_akses')->updateOrInsert(['id_user' => $user, 'id_menu' => $menu], ['lihat' => 1, 'beranda' => 0, 'tambah' => 1, 'edit' => 1, 'hapus' => 1]);
        }
    }

    public function down(): void
    {
        $ids = DB::table('menu')->where('route_name', 'rekap-kpr.index')->pluck('id');
        DB::table('hak_akses')->whereIn('id_menu', $ids)->delete();
        DB::table('role_user')->whereIn('id_menu', $ids)->delete();
        DB::table('menu')->whereIn('id', $ids)->delete();
        Schema::dropIfExists('rekap_kpr');
    }
};
