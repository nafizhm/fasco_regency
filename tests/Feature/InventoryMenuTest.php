<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryMenuTest extends TestCase
{
    public function test_management_menu_moves_existing_children_and_preserves_access(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('menu', function (Blueprint $t) {
            $t->id();
            $t->integer('id_parent')->default(0);
            $t->string('title');
            $t->string('route_name');
            $t->string('icon')->default('');
            $t->integer('urutan')->default(1);
            foreach (['lihat', 'tambah', 'edit', 'hapus'] as $field) {
                $t->integer($field)->default(1);
            }
        });
        foreach (['hak_akses' => 'id_user', 'role_user' => 'id_role'] as $table => $owner) {
            Schema::create($table, function (Blueprint $t) use ($owner) {
                $t->id();
                $t->integer($owner);
                $t->integer('id_menu');
                foreach (['lihat', 'tambah', 'edit', 'hapus', 'beranda'] as $field) {
                    $t->integer($field)->default(0);
                }
            });
            DB::table($table)->insert([
                [$owner => 1, 'id_menu' => 10, 'lihat' => 1, 'tambah' => 1],
                [$owner => 2, 'id_menu' => 11, 'lihat' => 1, 'tambah' => 0],
            ]);
        }
        DB::table('menu')->insert([
            ['id' => 10, 'title' => 'Barang Masuk', 'route_name' => 'barang-masuk.index'],
            ['id' => 11, 'title' => 'Barang Keluar', 'route_name' => 'barang-keluar.index'],
        ]);
        $migration = require database_path('migrations/2026_11_18_030000_add_manajemen_barang_menu.php');
        $migration->up();
        $migration->up();
        $parent = DB::table('menu')->where('title', 'Manajemen Barang')->value('id');
        $purchase = DB::table('menu')->where('route_name', 'pengajuan-pembelian.index')->value('id');
        $this->assertDatabaseCount('menu', 4);
        $this->assertDatabaseHas('menu', ['id' => 10, 'id_parent' => $parent, 'urutan' => 2]);
        $this->assertDatabaseHas('menu', ['id' => 11, 'id_parent' => $parent, 'urutan' => 3]);
        foreach (['hak_akses' => 'id_user', 'role_user' => 'id_role'] as $table => $owner) {
            $this->assertDatabaseHas($table, [$owner => 1, 'id_menu' => $purchase, 'lihat' => 1, 'tambah' => 1, 'edit' => 0]);
            $this->assertDatabaseMissing($table, [$owner => 2, 'id_menu' => $purchase]);
            $this->assertDatabaseHas($table, [$owner => 1, 'id_menu' => $parent, 'lihat' => 1]);
            $this->assertDatabaseHas($table, [$owner => 2, 'id_menu' => $parent, 'lihat' => 1]);
            $this->assertSame(5, DB::table($table)->count());
        }
    }
}
