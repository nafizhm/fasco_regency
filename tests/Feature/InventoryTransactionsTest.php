<?php

namespace Tests\Feature;

use App\Models\BarangMasuk;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryTransactionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        (require database_path('migrations/2026_11_10_010000_restore_inventory_modules.php'))->up();
        (require database_path('migrations/2026_11_10_030000_add_attachment_type_to_barang_masuk_lampiran.php'))->up();
        Schema::create('menu', function (Blueprint $t) { $t->id(); $t->string('route_name'); });
        Schema::create('hak_akses', function (Blueprint $t) {
            $t->id(); $t->integer('id_user'); $t->integer('id_menu');
            foreach (['lihat', 'tambah', 'edit', 'hapus'] as $column) $t->integer($column)->default(1);
        });
        Schema::create('konfigurasi', fn (Blueprint $t) => $t->id());
        Schema::create('konfigurasi_media', function (Blueprint $t) {
            $t->id(); $t->string('jenis_data'); $t->integer('stt_aktif')->default(1);
        });
        DB::table('menu')->insert(['id' => 1, 'route_name' => 'barang-masuk.index']);
        DB::table('hak_akses')->insert(['id_user' => 1, 'id_menu' => 1]);
        DB::table('satuan')->insert(['id' => 1, 'nama_satuan' => 'Sak']);
        DB::table('supplier')->insert(['id' => 1, 'nama' => 'Supplier Semen', 'status' => 1]);
        DB::table('barang')->insert(['id' => 1, 'nama' => 'Semen Gudang', 'id_satuan' => 1,
            'stok_awal' => 5, 'stok' => 5, 'harga_beli' => 50000]);
        $user = new User(); $user->id = 1; $user->username = 'inventory-test'; $this->actingAs($user);
    }

    private function payload(string $quantity = '10', string $price = '50.000'): array
    {
        return ['id_supplier' => 1, 'nama_penerima' => 'Petugas Gudang', 'tanggal' => '2026-11-01',
            'id_barang' => [1], 'jumlah' => [$quantity], 'harga_beli' => [$price], 'keterangan' => ['Pembelian semen']];
    }

    private function createIncoming(): int
    {
        $this->postJson(route('barang-masuk.store'), $this->payload())->assertOk();
        return (int) BarangMasuk::latest('id')->value('id');
    }

    public function test_incoming_create_update_delete_rebuilds_stock_and_history(): void
    {
        $id = $this->createIncoming();
        $this->assertDatabaseHas('barang', ['id' => 1, 'stok_awal' => 5, 'stok' => 15]);
        $this->assertDatabaseHas('barang_masuk', ['id' => $id, 'total_harga' => 500000, 'jum_item' => 1]);
        $this->assertDatabaseHas('history_barang', ['id_barang_masuk' => $id, 'masuk' => 10, 'stok' => 15]);
        $this->putJson(route('barang-masuk.update', $id), $this->payload('20'))->assertOk();
        $this->assertDatabaseHas('barang', ['id' => 1, 'stok_awal' => 5, 'stok' => 25]);
        $this->assertDatabaseHas('barang_masuk', ['id' => $id, 'total_harga' => 1000000]);
        $this->assertDatabaseCount('history_barang', 1);
        $this->deleteJson(route('barang-masuk.destroy', $id))->assertOk();
        $this->assertDatabaseHas('barang', ['id' => 1, 'stok_awal' => 5, 'stok' => 5]);
        $this->assertDatabaseCount('barang_masuk', 0);
        $this->assertDatabaseCount('barang_masuk_detail', 0);
        $this->assertDatabaseCount('history_barang', 0);
    }

    public function test_used_incoming_cannot_be_reduced_deleted_or_redated_before_negative_history(): void
    {
        $id = $this->createIncoming();
        DB::table('mutasi_stok')->insert(['tanggal' => '2026-11-02', 'id_barang' => 1,
            'id_barang_keluar' => 77, 'jumlah' => -12, 'stok_sisa' => 0]);
        app(StockService::class)->recalculateStokSisa(1);
        $this->assertDatabaseHas('barang', ['id' => 1, 'stok' => 3]);
        $this->putJson(route('barang-masuk.update', $id), $this->payload('6'))->assertUnprocessable();
        $this->deleteJson(route('barang-masuk.destroy', $id))->assertUnprocessable();
        $payload = $this->payload(); $payload['tanggal'] = '2026-11-03';
        $this->putJson(route('barang-masuk.update', $id), $payload)->assertUnprocessable();
        $this->assertDatabaseHas('barang', ['id' => 1, 'stok' => 3]);
        $this->assertDatabaseHas('barang_masuk', ['id' => $id, 'tanggal' => '2026-11-01', 'total_harga' => 500000]);
        $this->assertDatabaseHas('barang_masuk_detail', ['id_masuk' => $id, 'jumlah' => 10]);
        $this->assertDatabaseCount('mutasi_stok', 3);
    }

    public function test_invalid_numbers_and_mismatched_lines_do_not_create_stock(): void
    {
        foreach (['-1', '1.5', '1e3', '0', '1000000001'] as $quantity) {
            $this->postJson(route('barang-masuk.store'), $this->payload($quantity))->assertUnprocessable();
        }
        $this->postJson(route('barang-masuk.store'), $this->payload('10', '-50'))->assertUnprocessable();
        $payload = $this->payload(); $payload['jumlah'] = [2 => '10'];
        $this->postJson(route('barang-masuk.store'), $payload)->assertUnprocessable();
        $this->assertDatabaseCount('barang_masuk', 0);
        $this->assertDatabaseCount('mutasi_stok', 0);
        $this->assertDatabaseHas('barang', ['id' => 1, 'stok' => 5]);
    }

    public function test_payments_cannot_exceed_outstanding_and_paid_purchases_are_protected(): void
    {
        $id = $this->createIncoming();
        $url = route('barang-masuk.payment.store', $id);
        $this->postJson($url, ['tanggal_pembayaran' => '2026-11-01', 'jumlah_bayar' => '200.000'])->assertOk();
        $this->postJson($url, ['tanggal_pembayaran' => '2026-11-01', 'jumlah_bayar' => '300.001'])
            ->assertUnprocessable()->assertJsonValidationErrors('jumlah_bayar');
        $this->postJson($url, ['tanggal_pembayaran' => '2026-11-01', 'jumlah_bayar' => '0'])->assertUnprocessable();
        $this->postJson($url, ['tanggal_pembayaran' => '2026-11-01', 'jumlah_bayar' => '-1'])->assertUnprocessable();
        $this->putJson(route('barang-masuk.update', $id), $this->payload('1'))->assertUnprocessable();
        $this->deleteJson(route('barang-masuk.destroy', $id))->assertUnprocessable();
        $this->assertDatabaseHas('barang_masuk', ['id' => $id, 'terbayar' => 200000, 'total_harga' => 500000, 'status' => 0]);
        $this->postJson($url, ['tanggal_pembayaran' => '2026-11-02', 'jumlah_bayar' => '300.000'])->assertOk();
        $this->assertDatabaseHas('barang_masuk', ['id' => $id, 'terbayar' => 500000, 'status' => 1]);
        $this->putJson(route('barang-masuk.update', $id), $this->payload('20'))->assertUnprocessable();
        $this->postJson($url, ['tanggal_pembayaran' => '2026-11-02', 'jumlah_bayar' => '1'])->assertUnprocessable();
        $this->assertDatabaseCount('barang_masuk_pembayaran', 2);
    }

    public function test_server_checks_inventory_permissions_for_writes_and_reads(): void
    {
        $id = $this->createIncoming();
        DB::table('hak_akses')->update(['tambah' => 0, 'edit' => 0, 'hapus' => 0]);
        $this->postJson(route('barang-masuk.store'), $this->payload())->assertForbidden();
        $this->putJson(route('barang-masuk.update', $id), $this->payload())->assertForbidden();
        $this->deleteJson(route('barang-masuk.destroy', $id))->assertForbidden();
        $this->postJson(route('barang-masuk.payment.store', $id), ['tanggal_pembayaran' => '2026-11-02', 'jumlah_bayar' => '1'])->assertForbidden();
        DB::table('hak_akses')->update(['lihat' => 0]);
        $this->getJson(route('barang-masuk.payment-info', $id))->assertForbidden();
        $this->get(route('barang-masuk.show', $id))->assertForbidden();
    }

    public function test_incoming_create_and_edit_forms_render_with_current_layout(): void
    {
        $this->get(route('barang-masuk.create'))->assertOk()->assertSee('Tambah Barang Masuk')->assertSee('Semen Gudang');
        $id = $this->createIncoming();
        $this->get(route('barang-masuk.show', $id))->assertOk()->assertSee('Petugas Gudang')->assertSee('Semen Gudang');
    }

    public function test_optional_attachments_are_saved_with_their_category_and_shown_on_edit(): void
    {
        $files = [];
        try {
            $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aX1sAAAAASUVORK5CYII=');
            $payload = $this->payload();
            $payload['lampiran_nota'] = [\Illuminate\Http\UploadedFile::fake()->create('nota.pdf', 20, 'application/pdf')];
            $payload['lampiran_surat_jalan'] = [\Illuminate\Http\UploadedFile::fake()->create('surat-jalan.pdf', 20, 'application/pdf')];
            $payload['lampiran_foto_barang'] = [\Illuminate\Http\UploadedFile::fake()->createWithContent('barang.png', $png)];
            $this->post(route('barang-masuk.store'), $payload, ['Accept' => 'application/json'])->assertOk();
            $files = DB::table('barang_masuk_lampiran')->pluck('file_name')->all();
            $this->assertDatabaseCount('barang_masuk_lampiran', 3);
            foreach (['nota', 'surat_jalan', 'foto_barang'] as $category) {
                $this->assertDatabaseHas('barang_masuk_lampiran', ['jenis_lampiran' => $category]);
            }
            foreach ($files as $file) $this->assertFileExists(public_path('assets/barang-masuk/lampiran/'.$file));
            $this->get(route('barang-masuk.show', BarangMasuk::latest('id')->value('id')))->assertOk()
                ->assertSee('Nota Pembelian')->assertSee('Surat Jalan')->assertSee('Foto Barang Datang');
        } finally {
            foreach ($files as $file) @unlink(public_path('assets/barang-masuk/lampiran/'.$file));
        }
    }

    public function test_attachment_validation_rejects_pdf_for_goods_photos_and_oversized_documents(): void
    {
        $this->post(route('barang-masuk.store'), $this->payload() + [
            'lampiran_foto_barang' => [\Illuminate\Http\UploadedFile::fake()->create('barang.pdf', 20, 'application/pdf')],
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('lampiran_foto_barang.0');
        $this->post(route('barang-masuk.store'), $this->payload() + [
            'lampiran_nota' => [\Illuminate\Http\UploadedFile::fake()->create('nota.pdf', 2049, 'application/pdf')],
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('lampiran_nota.0');
        $this->assertDatabaseCount('barang_masuk', 0);
    }
}
