<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('construction_reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('upload_id')->unique();
            $table->unsignedBigInteger('unit_id')->index();
            $table->unsignedBigInteger('user_id');
            $table->date('reported_at');
            $table->decimal('percentage', 5, 2);
            $table->text('notes');
            $table->json('photos');
            $table->timestamps();
            $table->index(['unit_id', 'reported_at', 'id']);
        });
        $menu = DB::table('menu')->insertGetId([
            'id_parent' => 0, 'title' => 'Progres Pembangunan', 'route_name' => 'construction.index',
            'icon' => 'fas fa-helmet-safety', 'urutan' => (DB::table('menu')->max('urutan') ?? 0) + 1,
            'lihat' => 1, 'tambah' => 1, 'edit' => 0, 'hapus' => 0,
        ]);
        foreach (['hak_akses' => 'id_user', 'role_user' => 'id_role'] as $table => $key) {
            $owners = DB::table($table)->where('lihat', 1)->distinct()->pluck($key);
            foreach ($owners as $owner) {
                DB::table($table)->insert([$key => $owner, 'id_menu' => $menu, 'lihat' => 1,
                    'beranda' => 0, 'tambah' => 1, 'edit' => 0, 'hapus' => 0]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('menu')->where('route_name', 'construction.index')->pluck('id');
        foreach (['hak_akses', 'role_user'] as $table) {
            DB::table($table)->whereIn('id_menu', $ids)->delete();
        }
        DB::table('menu')->whereIn('id', $ids)->delete();
        Schema::dropIfExists('construction_reports');
    }
};
