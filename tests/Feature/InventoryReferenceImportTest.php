<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryReferenceImportTest extends TestCase
{
    public function test_import_maps_units_starts_at_zero_and_preserves_existing_stock_on_retry(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        (require database_path('migrations/2026_11_10_010000_restore_inventory_modules.php'))->up();
        Schema::create('menu', function (Blueprint $t) {
            $t->id(); $t->integer('id_parent')->default(0); $t->string('route_name');
        });
        foreach (['hak_akses', 'role_user'] as $table) {
            Schema::create($table, function (Blueprint $t) { $t->id(); $t->integer('id_menu'); });
        }
        foreach (['master-project.index', 'setting-proyek.index', 'pengajuan-barang.index', 'barang.index'] as $i => $route) {
            DB::table('menu')->insert(['id' => $i + 1, 'route_name' => $route]);
            DB::table('hak_akses')->insert(['id_menu' => $i + 1]);
            DB::table('role_user')->insert(['id_menu' => $i + 1]);
        }
        // Source IDs cannot be copied verbatim because target IDs may already be occupied.
        DB::table('satuan')->insert(['id' => 500, 'nama_satuan' => 'Sak']);
        $migration = require database_path('migrations/2026_11_10_020000_simplify_inventory_menus_and_import_master.php');
        $migration->up();
        $data = json_decode(file_get_contents(database_path('inventory_reference_master.json')), true);
        $this->assertDatabaseCount('satuan', count($data['satuan']));
        $this->assertDatabaseCount('barang', count($data['barang']));
        $this->assertSame(0, (int) DB::table('barang')->sum('stok'));
        $this->assertSame(0, (int) DB::table('barang')->sum('stok_awal'));
        $sourceUnitIds = array_column($data['satuan'], 'nama_satuan', 'id');
        foreach ($data['barang'] as $item) {
            $row = DB::table('barang')->where('nama', trim($item['nama']))->first();
            $this->assertSame((int) $item['harga_beli'], (int) $row->harga_beli);
            $this->assertSame($sourceUnitIds[$item['id_satuan']], DB::table('satuan')->where('id', $row->id_satuan)->value('nama_satuan'));
        }
        $this->assertDatabaseCount('menu', 1);
        $this->assertDatabaseCount('hak_akses', 1);
        $this->assertDatabaseCount('role_user', 1);
        $id = DB::table('barang')->value('id');
        DB::table('barang')->where('id', $id)->update(['stok' => 19, 'harga_beli' => 123]);
        $migration->up();
        $this->assertDatabaseCount('barang', count($data['barang']));
        $this->assertDatabaseHas('barang', ['id' => $id, 'stok' => 19, 'harga_beli' => 123]);
    }
}
