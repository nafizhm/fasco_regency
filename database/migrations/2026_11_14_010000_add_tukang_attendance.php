<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tukang', fn (Blueprint $t) => $t->unsignedBigInteger('gaji_per_hari')->default(0));
        Schema::create('absen_tukang_pekan', function (Blueprint $t) {
            $t->id();
            $t->date('mulai')->unique();
            $t->unsignedInteger('versi')->default(0);
            $t->timestamps();
        });
        Schema::create('absen_tukang_detail', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('id_pekan');
            $t->unsignedBigInteger('id_tukang');
            $t->unsignedBigInteger('gaji_per_hari')->default(0);
            $t->json('hari');
            $t->unique(['id_pekan', 'id_tukang']);
            $t->index('id_tukang');
        });

        DB::transaction(function () {
            $parent = DB::table('menu')->insertGetId([
                'id_parent' => 0, 'title' => 'Tukang', 'route_name' => '#',
                'icon' => 'fas fa-hard-hat', 'urutan' => (int) DB::table('menu')->where('id_parent', 0)->max('urutan') + 1,
                'lihat' => 1, 'tambah' => 0, 'edit' => 0, 'hapus' => 0,
            ]);
            $dataMenu = DB::table('menu')->where('route_name', 'tukang.index')->value('id');
            $values = ['id_parent' => $parent, 'title' => 'Data Tukang', 'route_name' => 'tukang.index',
                'icon' => 'fas fa-users', 'urutan' => 1, 'lihat' => 1, 'tambah' => 1, 'edit' => 1, 'hapus' => 1];
            if ($dataMenu) {
                DB::table('menu')->where('id', $dataMenu)->update($values);
            } else {
                $dataMenu = DB::table('menu')->insertGetId($values);
            }
            $attendance = DB::table('menu')->insertGetId([
                'id_parent' => $parent, 'title' => 'Absen Tukang', 'route_name' => 'absen-tukang.index',
                'icon' => 'fas fa-calendar-check', 'urutan' => 2, 'lihat' => 1, 'tambah' => 1, 'edit' => 1, 'hapus' => 0,
            ]);
            foreach (['hak_akses' => 'id_user', 'role_user' => 'id_role'] as $table => $key) {
                if (! Schema::hasTable($table)) continue;
                foreach (DB::table($table)->where('id_menu', $dataMenu)->get() as $access) {
                    DB::table($table)->insert([$key => $access->$key, 'id_menu' => $parent,
                        'lihat' => $access->lihat, 'tambah' => 0, 'edit' => 0, 'hapus' => 0, 'beranda' => 0]);
                    DB::table($table)->insert([$key => $access->$key, 'id_menu' => $attendance,
                        'lihat' => $access->lihat, 'tambah' => $access->tambah, 'edit' => $access->edit, 'hapus' => 0, 'beranda' => 0]);
                }
            }
            (new \Database\Seeders\TukangReferenceSeeder)->run();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Riwayat absensi dan gaji harus dipertahankan. Pulihkan backup untuk membatalkan migrasi.');
    }
};
