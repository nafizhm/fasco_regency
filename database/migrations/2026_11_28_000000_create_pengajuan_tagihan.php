<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pengajuan_tagihan')) {
            Schema::create('pengajuan_tagihan', function (Blueprint $table) {
                $table->id();
                $table->string('nomor', 100)->unique();
                $table->date('tanggal');
                $table->string('status', 30)->default('Draft');
                $table->text('keterangan')->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('pengajuan_tagihan_detail')) {
            Schema::create('pengajuan_tagihan_detail', function (Blueprint $table) {
                $table->id();
                $table->foreignId('id_pengajuan')->constrained('pengajuan_tagihan')->cascadeOnDelete();
                $table->date('tanggal_nota_max')->nullable();
                $table->string('nama');
                $table->text('uraian');
                foreach (['fasco_regency', 'tegalgondo', 'ruko', 'bedali', 'lumajang'] as $column) {
                    $table->unsignedBigInteger($column)->default(0);
                }
                $table->string('rekening', 500)->nullable();
                $table->string('lampiran')->nullable();
                $table->string('nama_lampiran')->nullable();
            });
        }
        $menu = DB::table('menu')->where('route_name', 'pengajuan-tagihan.index')->value('id');
        if (! $menu) {
            $menu = DB::table('menu')->insertGetId([
                'id_parent' => 0, 'title' => 'Pengajuan Tagihan', 'route_name' => 'pengajuan-tagihan.index',
                'icon' => 'fas fa-file-invoice', 'urutan' => (DB::table('menu')->max('urutan') ?? 0) + 1,
                'lihat' => 1, 'tambah' => 1, 'edit' => 1, 'hapus' => 1,
            ]);
        }
        foreach (DB::table('users')->whereRaw('LOWER(username) = ?', ['nita'])->pluck('id') as $user) {
            DB::table('hak_akses')->updateOrInsert(['id_user' => $user, 'id_menu' => $menu], ['lihat' => 1, 'beranda' => 0, 'tambah' => 1, 'edit' => 1, 'hapus' => 1]);
        }
    }

    public function down(): void
    {
        $ids = DB::table('menu')->where('route_name', 'pengajuan-tagihan.index')->pluck('id');
        DB::table('hak_akses')->whereIn('id_menu', $ids)->delete();
        DB::table('role_user')->whereIn('id_menu', $ids)->delete();
        DB::table('menu')->whereIn('id', $ids)->delete();
        Schema::dropIfExists('pengajuan_tagihan_detail');
        Schema::dropIfExists('pengajuan_tagihan');
    }
};
