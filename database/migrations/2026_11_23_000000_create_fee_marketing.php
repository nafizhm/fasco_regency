<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fee_marketing')) {
            Schema::create('fee_marketing', function (Blueprint $t) {
                $t->id();
                $t->string('nomor', 150)->unique();
                $t->date('tanggal')->index();
                $t->unsignedBigInteger('jumlah')->default(0);
                $t->longText('data');
                $t->timestamps();
            });
        }
        DB::transaction(function () {
            $source = DB::table('menu')->where('route_name', 'pemasukan.index')->first();
            if (! $source) throw new RuntimeException('Menu Pemasukan harus tersedia sebelum Fee Marketing.');
            $id = DB::table('menu')->where('route_name', 'fee-marketing.index')->value('id');
            if (! $id) {
                DB::table('menu')->where('id_parent', $source->id_parent)->where('urutan', '>', $source->urutan)->increment('urutan');
                $id = DB::table('menu')->insertGetId([
                    'id_parent' => $source->id_parent, 'title' => 'Fee Marketing', 'route_name' => 'fee-marketing.index',
                    'icon' => 'fas fa-hand-holding-usd', 'urutan' => $source->urutan + 1,
                    'lihat' => 1, 'tambah' => 1, 'edit' => 1, 'hapus' => 1,
                ]);
            }
            foreach (['hak_akses' => 'id_user', 'role_user' => 'id_role'] as $table => $key) {
                if (! Schema::hasTable($table)) continue;
                foreach (DB::table($table)->where('id_menu', $source->id)->get() as $access) {
                    if (DB::table($table)->where($key, $access->$key)->where('id_menu', $id)->exists()) continue;
                    DB::table($table)->insert([
                        $key => $access->$key, 'id_menu' => $id, 'beranda' => 0,
                        'lihat' => $access->lihat, 'tambah' => $access->tambah,
                        'edit' => $access->edit, 'hapus' => $access->hapus,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        $ids = DB::table('menu')->where('route_name', 'fee-marketing.index')->pluck('id');
        foreach (['hak_akses', 'role_user'] as $table) {
            if (Schema::hasTable($table)) DB::table($table)->whereIn('id_menu', $ids)->delete();
        }
        DB::table('menu')->whereIn('id', $ids)->delete();
        Schema::dropIfExists('fee_marketing');
    }
};

