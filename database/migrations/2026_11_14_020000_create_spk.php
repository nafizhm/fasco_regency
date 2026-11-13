<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spk', function (Blueprint $t) {
            $t->id();
            $t->string('nomor')->unique();
            $t->date('tanggal');
            foreach (['nama_perusahaan', 'telp_perusahaan', 'penandatangan', 'kota', 'nama_pemborong',
                'jabatan_pemborong', 'nama_proyek', 'lama_pengerjaan'] as $field) $t->string($field);
            foreach (['alamat_perusahaan', 'alamat_pemborong', 'lokasi_proyek', 'jenis_pekerjaan', 'skema_pembayaran'] as $field) $t->text($field);
            $t->date('tanggal_mulai');
            $t->date('tanggal_selesai')->nullable();
            $t->json('items');
            $t->unsignedBigInteger('total_rincian');
            $t->unsignedBigInteger('nilai_borongan');
            $t->unsignedInteger('versi')->default(0);
            $t->timestamps();
        });
        Schema::create('spk_pembayaran', function (Blueprint $t) {
            $t->id();
            $t->foreignId('id_spk')->constrained('spk')->restrictOnDelete();
            $t->date('tanggal');
            $t->unsignedBigInteger('nominal');
            $t->string('metode', 100)->nullable();
            $t->string('referensi')->nullable();
            $t->text('keterangan')->nullable();
            $t->string('token', 36)->unique();
            $t->timestamps();
        });
        DB::transaction(function () {
            $id = DB::table('menu')->insertGetId([
                'id_parent' => 0, 'title' => 'SPK', 'route_name' => 'spk.index',
                'icon' => 'fas fa-file-contract', 'urutan' => (int) DB::table('menu')->where('id_parent', 0)->max('urutan') + 1,
                'lihat' => 1, 'tambah' => 1, 'edit' => 1, 'hapus' => 1,
            ]);
            $adminMenu = DB::table('menu')->where('route_name', 'hak-akses.index')->value('id');
            foreach (['hak_akses' => 'id_user', 'role_user' => 'id_role'] as $table => $key) {
                if (! Schema::hasTable($table) || ! $adminMenu) continue;
                foreach (DB::table($table)->where('id_menu', $adminMenu)->where('lihat', 1)->where('edit', 1)->distinct()->pluck($key) as $owner) {
                    DB::table($table)->insert([$key => $owner, 'id_menu' => $id, 'lihat' => 1, 'tambah' => 1, 'edit' => 1, 'hapus' => 1, 'beranda' => 0]);
                }
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException('SPK dan riwayat pembayaran harus dipertahankan. Gunakan backup untuk membatalkan migrasi.');
    }
};
