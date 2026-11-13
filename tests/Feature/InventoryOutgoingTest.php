<?php

namespace Tests\Feature;

use App\Http\Controllers\BarangKeluarController;
use App\Http\Controllers\Pengaturan\SettingProyekController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryOutgoingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        (require database_path('migrations/2026_11_10_010000_restore_inventory_modules.php'))->up();
        (require database_path('migrations/2026_11_10_050000_add_manual_work_type_to_barang_keluar_detail.php'))->up();
        Schema::create('menu', function (Blueprint $t) { $t->id(); $t->string('route_name'); });
        Schema::create('hak_akses', function (Blueprint $t) {
            $t->id(); $t->integer('id_user'); $t->integer('id_menu');
            foreach (['lihat', 'tambah', 'edit', 'hapus'] as $column) $t->integer($column)->default(1);
        });
        Schema::create('lokasi_kavling', function (Blueprint $t) { $t->id(); $t->string('nama_kavling')->nullable(); });
        Schema::create('kavling_peta', function (Blueprint $t) { $t->id(); $t->integer('id_lokasi'); $t->string('kode_kavling'); $t->string('nama_cluster')->nullable(); });
        foreach (['barang-keluar.index', 'setting-proyek.index'] as $i => $route) {
            DB::table('menu')->insert(['id' => $i+1, 'route_name' => $route]);
            DB::table('hak_akses')->insert(['id_user' => 1, 'id_menu' => $i+1]);
        }
        DB::table('lokasi_kavling')->insert(['id' => 1, 'nama_kavling' => 'Fasco']);
        DB::table('kavling_peta')->insert(['id' => 27, 'id_lokasi' => 1, 'kode_kavling' => '03']);
        DB::table('barang')->insert(['id' => 1, 'nama' => 'Semen', 'stok_awal' => 10, 'stok' => 10]);
        DB::table('tukang')->insert(['id' => 1, 'nama' => 'Tukang A']);
        DB::table('master_project')->insert(['id' => 1, 'jenis_proyek' => 'Tipe 36', 'jenis_pekerjaan' => 'pembangunan_unit']);
        DB::table('master_project_pekerjaan')->insert(['id' => 1, 'id_master_project' => 1, 'nama_pekerjaan' => 'Pondasi']);
        DB::table('setting_proyek')->insert(['id_lokasi' => 1, 'id_kavling' => 27, 'id_master_project' => 1]);
        $user = new User(); $user->id = 1; $this->actingAs($user);
        Route::post('/_test/outgoing', [BarangKeluarController::class, 'store']);
        Route::put('/_test/outgoing/{id}', [BarangKeluarController::class, 'update']);
        Route::delete('/_test/outgoing/{id}', [BarangKeluarController::class, 'destroy']);
        Route::get('/_test/outgoing/units/{id}', [BarangKeluarController::class, 'getUnitsByLokasi']);
        Route::post('/_test/settings', [SettingProyekController::class, 'store']);
    }

    private function payload($qty = '4'): array
    {
        return ['mode' => 'unit', 'tanggal' => '2026-11-10', 'id_lokasi' => 1, 'id_kavling' => 27,
            'id_tukang' => 1, 'id_barang' => [1], 'jumlah' => [$qty], 'id_master_project_pekerjaan' => [1]];
    }

    public function test_unit_options_include_clusters_to_distinguish_identical_unit_numbers(): void
    {
        DB::table('kavling_peta')->where('id', 27)->update(['kode_kavling' => '01', 'nama_cluster' => 'BIRU']);
        DB::table('kavling_peta')->insert(['id' => 28, 'id_lokasi' => 1, 'kode_kavling' => '01', 'nama_cluster' => 'JINGGA']);
        $response = $this->getJson(route('barang-keluar.unit-by-lokasi', ['id' => 1]))->assertOk();
        $response->assertJsonFragment(['id_kavling' => 27, 'kode_kavling' => '01', 'nama_cluster' => 'BIRU']);
        $response->assertJsonFragment(['id_kavling' => 28, 'kode_kavling' => '01', 'nama_cluster' => 'JINGGA']);
    }

    public function test_manual_work_does_not_require_project_settings(): void
    {
        DB::table('setting_proyek')->delete();
        DB::table('master_project_pekerjaan')->delete();
        DB::table('master_project')->delete();
        $this->getJson(route('barang-keluar.unit-by-lokasi', ['id' => 1]))
            ->assertOk()->assertJsonFragment(['id_kavling' => 27, 'kode_kavling' => '03']);
        $payload = $this->payload();
        unset($payload['id_master_project_pekerjaan']);
        $payload['jenis_pekerjaan'] = ['Pemasangan granit'];
        $this->postJson(route('barang-keluar.store'), $payload)->assertOk();
        $id = DB::table('barang_keluar')->value('id');
        $this->assertDatabaseHas('barang_keluar_detail', ['id_barang_keluar' => $id,
            'jenis_pekerjaan' => 'Pemasangan granit', 'id_master_project_pekerjaan' => null]);
        $payload['jenis_pekerjaan'] = ['Finishing dinding'];
        $this->putJson(route('barang-keluar.update', $id), $payload)->assertOk();
        $this->assertDatabaseHas('barang_keluar_detail', ['id_barang_keluar' => $id, 'jenis_pekerjaan' => 'Finishing dinding']);
        $this->assertDatabaseHas('barang', ['id' => 1, 'stok' => 6]);
        $payload['jenis_pekerjaan'] = [''];
        $this->postJson(route('barang-keluar.store'), $payload)->assertUnprocessable();
        $payload['jenis_pekerjaan'] = ['Pondasi'];
        DB::table('lokasi_kavling')->insert(['id' => 2, 'nama_kavling' => 'Lokasi lain']);
        $payload['id_lokasi'] = 2;
        $this->postJson(route('barang-keluar.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('id_kavling');
    }

    public function test_outgoing_create_update_delete_recalculates_stock_and_history(): void
    {
        $this->postJson('/_test/outgoing', $this->payload())->assertOk();
        $this->assertDatabaseHas('barang', ['id' => 1, 'stok' => 6]);
        $this->assertDatabaseHas('history_barang', ['tipe' => 'KELUAR', 'keluar' => 4, 'stok' => 6]);
        $this->putJson('/_test/outgoing/1', $this->payload('2'))->assertOk();
        $this->assertDatabaseHas('barang', ['id' => 1, 'stok' => 8]);
        $this->deleteJson('/_test/outgoing/1')->assertOk();
        $this->assertDatabaseHas('barang', ['id' => 1, 'stok' => 10]);
        $this->assertDatabaseCount('history_barang', 0);
    }

    public function test_invalid_or_excess_quantities_do_not_change_stock(): void
    {
        foreach (['-1', '0', '11', '1.5', 'abc'] as $qty) {
            $this->postJson('/_test/outgoing', $this->payload($qty))->assertUnprocessable();
        }
        $this->assertDatabaseCount('barang_keluar', 0);
        $this->assertDatabaseHas('barang', ['id' => 1, 'stok' => 10]);
    }

    public function test_work_must_belong_to_the_configured_project_and_stock_rolls_back(): void
    {
        DB::table('master_project_pekerjaan')->insert(['id' => 2, 'id_master_project' => 99, 'nama_pekerjaan' => 'Lain']);
        $payload = $this->payload(); $payload['id_master_project_pekerjaan'] = [2];
        $this->postJson('/_test/outgoing', $payload)->assertUnprocessable();
        $this->assertDatabaseCount('barang_keluar', 0);
        $this->assertDatabaseCount('barang_keluar_detail', 0);
    }

    public function test_units_endpoint_uses_existing_primary_keys_and_enforces_access(): void
    {
        $this->getJson('/_test/outgoing/units/1')->assertOk()->assertJsonPath('0.id_kavling', 27);
        DB::table('hak_akses')->update(['lihat' => 0]);
        $this->getJson('/_test/outgoing/units/1')->assertForbidden();
        $this->postJson('/_test/outgoing', $this->payload())->assertForbidden();
    }

    public function test_project_setting_rejects_units_from_a_different_location(): void
    {
        DB::table('kavling_peta')->insert(['id' => 28, 'id_lokasi' => 2, 'kode_kavling' => '04']);
        $this->postJson('/_test/settings', ['id_lokasi' => 1, 'id_kavling' => 28,
            'id_master_project' => 1, 'status_proyek' => 1])->assertUnprocessable()->assertJsonValidationErrors('id_kavling');
        $this->assertDatabaseCount('setting_proyek', 1);
    }

    private function prepareLayout(): void
    {
        Schema::table('menu', function (Blueprint $t) {
            $t->integer('id_parent')->default(1); $t->integer('urutan')->default(0);
        });
        Schema::create('konfigurasi', function (Blueprint $t) { $t->id(); $t->string('nama_perusahaan')->nullable(); });
        Schema::create('konfigurasi_media', function (Blueprint $t) { $t->id(); $t->string('jenis_data'); $t->integer('stt_aktif'); $t->string('nama_file')->nullable(); });
        Schema::create('perusahaan', function (Blueprint $t) { $t->id(); $t->string('nama_perusahaan'); });
        Schema::create('lokasi_kavling_perusahaan', function (Blueprint $t) { $t->id(); $t->integer('id_lokasi'); $t->integer('id_perusahaan'); });
        DB::table('perusahaan')->insert(['id' => 1, 'nama_perusahaan' => 'Fasco Developer']);
        DB::table('lokasi_kavling_perusahaan')->insert(['id_lokasi' => 1, 'id_perusahaan' => 1]);
        DB::table('menu')->insert(['id' => 3, 'route_name' => 'master-project.index']);
        DB::table('hak_akses')->insert(['id_user' => 1, 'id_menu' => 3]);
        Route::get('/_test/outgoing/show/{id}', [BarangKeluarController::class, 'show'])->name('test.outgoing.show');
        Route::get('/_test/outgoing/nota/{id}', [BarangKeluarController::class, 'nota'])->name('test.outgoing.nota');
        Route::get('/_test/project/{id}/pekerjaan', [\App\Http\Controllers\Pengaturan\MasterProjectController::class, 'pekerjaanIndex'])->name('test.project.pekerjaan');
        Route::get('/_test/project/{id}/material', [\App\Http\Controllers\Pengaturan\MasterProjectController::class, 'material'])->name('test.project.material');
        Route::get('/_test/project/{id}/jasa', [\App\Http\Controllers\Pengaturan\MasterProjectController::class, 'jasa'])->name('test.project.jasa');
        Route::get('/_test/settings', [SettingProyekController::class, 'index'])->name('test.settings');
    }

    public function test_populated_outgoing_edit_and_receipt_render_with_the_current_layout(): void
    {
        $this->prepareLayout();
        $this->postJson('/_test/outgoing', $this->payload())->assertOk();
        $this->get('/_test/outgoing/show/1')->assertOk()->assertSee('Semen')->assertSee('Pondasi')->assertSee('03');
        $this->get('/_test/outgoing/nota/1')->assertOk()->assertSee('Fasco Developer')->assertSee('Semen')->assertSee('Tukang A');
    }

    public function test_populated_master_work_material_service_and_settings_render(): void
    {
        $this->prepareLayout();
        DB::table('satuan')->insert(['id' => 1, 'nama_satuan' => 'Sak']);
        DB::table('barang')->where('id', 1)->update(['id_satuan' => 1]);
        DB::table('master_project_detail')->insert([
            ['id_master_project' => 1, 'id_master_project_pekerjaan' => 1, 'tipe' => 'material', 'id_barang' => 1, 'nama_item' => 'Semen', 'satuan' => 'Sak', 'qty' => 2, 'harga_satuan' => 50000, 'sub_total' => 100000],
            ['id_master_project' => 1, 'id_master_project_pekerjaan' => 1, 'tipe' => 'jasa', 'id_barang' => null, 'nama_item' => 'Pasang pondasi', 'satuan' => 'M2', 'qty' => 2, 'harga_satuan' => 30000, 'sub_total' => 60000],
        ]);
        $this->get('/_test/project/1/pekerjaan')->assertOk()->assertSee('Pondasi')->assertSee('Tipe 36');
        $this->get('/_test/project/1/material')->assertOk()->assertSee('Semen')->assertSee('Sak');
        $this->get('/_test/project/1/jasa')->assertOk()->assertSee('Pasang pondasi');
        $this->get('/_test/settings')->assertOk()->assertSee('Tipe 36')->assertSee('Fasco');
    }
}
