<?php

namespace Tests\Feature;

use App\Http\Controllers\Master\BarangController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryMasterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('menu', function (Blueprint $t) { $t->id(); $t->string('route_name'); });
        Schema::create('hak_akses', function (Blueprint $t) {
            $t->id(); $t->integer('id_user'); $t->integer('id_menu');
            foreach (['lihat', 'tambah', 'edit', 'hapus'] as $column) $t->integer($column)->default(1);
        });
        Schema::create('satuan', function (Blueprint $t) { $t->id(); $t->string('nama_satuan'); });
        Schema::create('barang', function (Blueprint $t) {
            $t->id(); $t->string('nama'); $t->integer('id_satuan');
            foreach (['stok_awal', 'stok', 'stok_minimal', 'harga_beli'] as $column) $t->bigInteger($column)->default(0);
            $t->string('sku')->nullable(); $t->text('deskripsi')->nullable();
        });
        Schema::create('history_barang', function (Blueprint $t) { $t->id(); $t->integer('id_barang'); });
        DB::table('menu')->insert(['id' => 1, 'route_name' => 'barang.index']);
        DB::table('hak_akses')->insert(['id_user' => 1, 'id_menu' => 1]);
        DB::table('satuan')->insert(['id' => 1, 'nama_satuan' => 'Sak']);
        DB::table('barang')->insert(['id' => 1, 'nama' => 'Semen', 'id_satuan' => 1,
            'stok_awal' => 10, 'stok' => 15, 'harga_beli' => 50000]);
        $user = new User(); $user->id = 1; $this->actingAs($user);
        Route::put('/_test/inventory-master/{id}', [BarangController::class, 'update']);
        Route::post('/_test/inventory-master', [BarangController::class, 'store']);
        Route::delete('/_test/inventory-master/{id}', [BarangController::class, 'destroy']);
    }

    private function payload(string $stock = '15'): array
    {
        return ['nama' => 'Semen', 'id_satuan' => 1, 'stok' => $stock,
            'stok_minimal' => '5', 'harga_beli' => '55.000'];
    }

    public function test_editing_master_preserves_opening_stock_after_movements(): void
    {
        DB::table('history_barang')->insert(['id_barang' => 1]);
        $this->putJson('/_test/inventory-master/1', $this->payload())->assertOk();
        $this->assertDatabaseHas('barang', ['id' => 1, 'stok_awal' => 10, 'stok' => 15, 'harga_beli' => 55000]);
        $this->putJson('/_test/inventory-master/1', $this->payload('16'))->assertUnprocessable();
        $this->assertDatabaseHas('barang', ['id' => 1, 'stok_awal' => 10, 'stok' => 15]);
    }

    public function test_used_goods_cannot_be_deleted(): void
    {
        DB::table('history_barang')->insert(['id_barang' => 1]);
        $this->deleteJson('/_test/inventory-master/1')->assertUnprocessable();
        $this->assertDatabaseHas('barang', ['id' => 1]);
    }

    public function test_invalid_stock_and_unknown_unit_are_rejected(): void
    {
        $payload = $this->payload('-1'); $payload['id_satuan'] = 99; $payload['nama'] = 'Barang Baru';
        $this->postJson('/_test/inventory-master', $payload)->assertUnprocessable()
            ->assertJsonValidationErrors(['stok', 'id_satuan']);
        $this->assertDatabaseCount('barang', 1);
    }

    public function test_write_access_is_enforced_on_server(): void
    {
        DB::table('hak_akses')->update(['edit' => 0, 'hapus' => 0, 'tambah' => 0]);
        $this->putJson('/_test/inventory-master/1', $this->payload())->assertForbidden();
        $this->deleteJson('/_test/inventory-master/1')->assertForbidden();
        $this->postJson('/_test/inventory-master', $this->payload())->assertForbidden();
    }
}
