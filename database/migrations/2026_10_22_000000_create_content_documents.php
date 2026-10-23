<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category', 100);
            $table->string('original_name');
            $table->string('path')->unique();
            $table->string('mime');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
        $menu = DB::table('menu')->insertGetId([
            'id_parent' => 0, 'title' => 'Dokumentasi Konten', 'route_name' => 'content-documents.index',
            'icon' => 'fas fa-photo-video', 'urutan' => (DB::table('menu')->max('urutan') ?? 0) + 1,
            'lihat' => 1, 'tambah' => 1, 'edit' => 0, 'hapus' => 0,
        ]);
        foreach (['hak_akses' => 'id_user', 'role_user' => 'id_role'] as $table => $key) {
            foreach (DB::table($table)->where('lihat', 1)->distinct()->pluck($key) as $owner) {
                DB::table($table)->insert([$key => $owner, 'id_menu' => $menu, 'lihat' => 1,
                    'beranda' => 0, 'tambah' => 1, 'edit' => 0, 'hapus' => 0]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('menu')->where('route_name', 'content-documents.index')->pluck('id');
        foreach (['hak_akses', 'role_user'] as $table) {
            DB::table($table)->whereIn('id_menu', $ids)->delete();
        }
        DB::table('menu')->whereIn('id', $ids)->delete();
        Schema::dropIfExists('content_documents');
    }
};
