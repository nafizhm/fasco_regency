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
        (require database_path('migrations/2026_11_10_040000_add_jatuh_tempo_to_barang_masuk.php'))->up();
        (require database_path('migrations/2026_11_18_020000_create_pengajuan_pembelian.php'))->up();
        (require database_path('migrations/2026_11_19_000000_add_harga_to_pengajuan_pembelian_detail.php'))->up();
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
        DB::table('pengajuan_pembelian')->insert(['id' => 1, 'nomor' => 'PP-TEST', 'tanggal' => '2026-10-01', 'id_supplier' => 1]);
        DB::table('pengajuan_pembelian_detail')->insert(['id_pengajuan' => 1, 'id_barang' => 1, 'jumlah' => 1000000000]);
        $user = new User(); $user->id = 1; $user->username = 'inventory-test'; $this->actingAs($user);
    }

    public function test_purchase_pdf_contains_purchase_details_and_requires_view_access(): void
    {
        DB::table('menu')->insert(['id' => 2, 'route_name' => 'pengajuan-pembelian.index']);
        DB::table('hak_akses')->insert(['id_user' => 1, 'id_menu' => 2]);
        DB::table('pengajuan_pembelian_detail')->update(['jumlah' => 2, 'harga' => 50000]);
        $purchase = \App\Models\PengajuanPembelian::with('supplier')->findOrFail(1);
        $lines = app(\App\Services\PurchaseReceivingService::class)->lines($purchase);
        $html = view('admin.pengajuan_pembelian.pdf', ['data' => $purchase, 'lines' => $lines])->render();
        foreach (['PP-TEST', '01-10-2026', 'Supplier Semen', 'Semen Gudang', 'Sak', '50.000', '100.000'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $response = $this->get(route('pengajuan-pembelian.pdf', 1))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('purchase-order-1.pdf', $response->headers->get('Content-Disposition'));
        $this->get(route('pengajuan-pembelian.pdf', 99999))->assertNotFound();
        DB::table('hak_akses')->where('id_menu', 2)->update(['lihat' => 0]);
        $this->get(route('pengajuan-pembelian.pdf', 1))->assertForbidden();
    }

    private function payload(string $quantity = '10', string $price = '50.000'): array
    {
        return ['id_pengajuan_pembelian' => 1, 'id_supplier' => 1, 'nama_penerima' => 'Petugas Gudang', 'tanggal' => '2026-11-01',
            'id_barang' => [1], 'jumlah' => [$quantity], 'harga_beli' => [$price], 'keterangan' => ['Pembelian semen']];
    }

    private function createIncoming(): int
    {
        $this->postJson(route('barang-masuk.store'), $this->payload())->assertOk();
        return (int) BarangMasuk::latest('id')->value('id');
    }

    public function test_incoming_nota_opens_as_pdf_with_receipt_filename_and_requires_access(): void
    {
        $this->mock(\App\Services\InventoryCompany::class, function ($mock) {
            $mock->shouldReceive('details')->once()->andReturn([
                'nama_perusahaan' => 'Perusahaan Test', 'alamat' => 'Malang', 'telp' => '',
                'kota' => 'Malang', 'nama_penandatangan' => 'Nama Lama', 'jabatan_penandatangan' => 'Admin',
            ]);
        });
        $id = $this->createIncoming();
        $response = $this->get(route('barang-masuk.nota', $id))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('BM-00001.pdf', $response->headers->get('Content-Disposition'));
        DB::table('hak_akses')->update(['lihat' => 0]);
        $this->get(route('barang-masuk.nota', $id))->assertForbidden();
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

    public function test_payment_due_date_is_optional_saved_editable_and_cannot_precede_receipt(): void
    {
        $payload = $this->payload();
        $this->postJson(route('barang-masuk.store'), $payload + ['jatuh_tempo' => '2026-10-31'])
            ->assertUnprocessable()->assertJsonValidationErrors('jatuh_tempo');
        $this->postJson(route('barang-masuk.store'), $payload + ['jatuh_tempo' => '2026-11-30'])->assertOk();
        $id = (int) BarangMasuk::latest('id')->value('id');
        $this->assertDatabaseHas('barang_masuk', ['id' => $id, 'jatuh_tempo' => '2026-11-30']);
        $this->get(route('barang-masuk.show', $id))->assertOk()->assertSee('2026-11-30');
        $this->putJson(route('barang-masuk.update', $id), $payload + ['jatuh_tempo' => '2026-12-15'])->assertOk();
        $this->assertDatabaseHas('barang_masuk', ['id' => $id, 'jatuh_tempo' => '2026-12-15']);
        $this->putJson(route('barang-masuk.update', $id), $payload + ['jatuh_tempo' => ''])->assertOk();
        $this->assertDatabaseHas('barang_masuk', ['id' => $id, 'jatuh_tempo' => null]);
    }

    public function test_list_orders_unpaid_by_due_date_then_settled_and_displays_remaining_days(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-10 12:00:00', 'Asia/Jakarta'));
        foreach ([
            ['Lunas', '2026-11-02', 100],
            ['Nanti', '2026-11-13', 0],
            ['Tanpa Tempo', null, 0],
            ['Hari Ini', '2026-11-10', 0],
            ['Terlambat', '2026-11-08', 0],
        ] as [$name, $due, $paid]) {
            DB::table('barang_masuk')->insert(['id_supplier' => 1, 'nama_penerima' => $name,
                'tanggal' => '2026-11-01', 'total_harga' => 100, 'terbayar' => $paid, 'jatuh_tempo' => $due]);
        }
        $response = $this->getJson(route('barang-masuk.index'), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $rows = $response->json('data');
        $this->assertSame(['Terlambat', 'Hari Ini', 'Nanti', 'Tanpa Tempo', 'Lunas'], array_column($rows, 'nama_penerima'));
        $this->assertStringContainsString('Terlambat 2 hari', $rows[0]['status_pembayaran']);
        $this->assertStringContainsString('Jatuh tempo hari ini', $rows[1]['status_pembayaran']);
        $this->assertStringContainsString('3 hari lagi', $rows[2]['status_pembayaran']);
        $this->assertStringContainsString('13 November 2026', $rows[2]['status_pembayaran']);
        $this->assertStringNotContainsString('Terlambat', $rows[4]['status_pembayaran']);
        $this->assertStringContainsString('aria-label="Pembayaran"', $rows[0]['action']);
        $this->assertStringNotContainsString('>Pembayaran</button>', $rows[0]['action']);
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

    private function enablePurchases(): void
    {
        DB::table('menu')->insert(['id' => 2, 'route_name' => 'pengajuan-pembelian.index']);
        DB::table('hak_akses')->insert(['id_user' => 1, 'id_menu' => 2]);
    }

    public function test_purchase_records_plan_without_stock_and_forms_render(): void
    {
        $this->enablePurchases();
        $payload = ['nomor' => 'PP-NEW', 'tanggal' => '2026-11-01', 'id_supplier' => 1,
            'id_barang' => [1], 'jumlah' => ['12'], 'harga' => ['50.000'], 'keterangan' => 'Rencana semen'];
        $this->post(route('pengajuan-pembelian.store'), $payload)->assertRedirect();
        $purchase = \App\Models\PengajuanPembelian::where('nomor', 'PP-NEW')->firstOrFail();
        $this->assertDatabaseHas('pengajuan_pembelian_detail', ['id_pengajuan' => $purchase->id, 'jumlah' => 12, 'harga' => 50000]);
        $this->assertDatabaseHas('barang', ['id' => 1, 'stok' => 5]);
        $this->assertDatabaseCount('barang_masuk', 0);
        $this->get(route('pengajuan-pembelian.index'))->assertOk()->assertSee('PP-NEW');
        $this->get(route('pengajuan-pembelian.create'))->assertOk()->assertSee('Rencana Pembelian Barang');
        $this->get(route('pengajuan-pembelian.show', $purchase->id))->assertOk()->assertSee('Belum diterima')->assertSee('600.000');
        $this->get(route('pengajuan-pembelian.edit', $purchase->id))->assertOk();
        $payload['jumlah'] = ['15'];
        $payload['harga'] = ['60.000'];
        $this->put(route('pengajuan-pembelian.update', $purchase->id), $payload)->assertRedirect();
        $this->assertDatabaseHas('pengajuan_pembelian_detail', ['id_pengajuan' => $purchase->id, 'jumlah' => 15, 'harga' => 60000]);
        $this->get(route('pengajuan-pembelian.show', $purchase->id))->assertOk()->assertSee('900.000');
        $this->delete(route('pengajuan-pembelian.destroy', $purchase->id))->assertRedirect();
        $this->assertDatabaseMissing('pengajuan_pembelian', ['id' => $purchase->id]);
    }

    public function test_purchase_list_status_tracks_full_receipt_and_details_load_as_modal_fragment(): void
    {
        $this->enablePurchases();
        DB::table('pengajuan_pembelian_detail')->update(['jumlah' => 10]);
        $this->get(route('pengajuan-pembelian.index'))->assertOk()->assertSee('Masih Terpesan')->assertSee('purchaseDetailModal');
        $this->postJson(route('barang-masuk.store'), $this->payload('4'))->assertOk();
        $this->get(route('pengajuan-pembelian.index'))->assertOk()->assertSee('Masih Terpesan');
        $this->postJson(route('barang-masuk.store'), $this->payload('6'))->assertOk();
        $this->get(route('pengajuan-pembelian.index'))->assertOk()->assertSee('Sudah Diterima')->assertDontSee('Masih Terpesan');
        $this->get(route('pengajuan-pembelian.show', 1), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertSee('Subtotal (Rp)')->assertSee('Sudah diterima semua')->assertDontSee('content-wrapper');
        $this->deleteJson(route('barang-masuk.destroy', 1))->assertOk();
        $this->get(route('pengajuan-pembelian.index'))->assertOk()->assertSee('Masih Terpesan');
    }

    public function test_purchase_list_prioritizes_unreceived_and_includes_all_datatable_rows(): void
    {
        $this->enablePurchases();
        DB::table('pengajuan_pembelian_detail')->update(['jumlah' => 10]);
        $this->postJson(route('barang-masuk.store'), $this->payload('10'))->assertOk();
        for ($i = 2; $i <= 28; $i++) {
            DB::table('pengajuan_pembelian')->insert(['id' => $i, 'nomor' => 'PP-PENDING-'.$i, 'tanggal' => '2026-11-01', 'id_supplier' => 1]);
            DB::table('pengajuan_pembelian_detail')->insert(['id_pengajuan' => $i, 'id_barang' => 1, 'jumlah' => 10]);
        }
        $this->get(route('pengajuan-pembelian.index'))->assertOk()
            ->assertViewHas('items', fn ($items) => $items->count() === 28 && $items->first()->id === 28 && $items->last()->id === 1)
            ->assertSee('id="purchaseTable"', false)->assertSee('PP-PENDING-2');
    }

    public function test_partial_receipts_prefill_all_goods_and_reject_over_delivery(): void
    {
        DB::table('pengajuan_pembelian_detail')->update(['jumlah' => 10]);
        DB::table('barang')->insert(['id' => 2, 'nama' => 'Pasir', 'id_satuan' => 1, 'stok_awal' => 0, 'stok' => 0]);
        DB::table('pengajuan_pembelian_detail')->insert(['id_pengajuan' => 1, 'id_barang' => 2, 'jumlah' => 20]);
        $payload = $this->payload('4', '60.000');
        $payload['id_barang'] = [1, 2]; $payload['jumlah'] = ['4', '0']; $payload['harga_beli'] = ['60.000', '0'];
        $this->postJson(route('barang-masuk.store'), $payload)->assertOk();
        $first = BarangMasuk::firstOrFail();
        $this->assertDatabaseHas('barang_masuk', ['id' => $first->id, 'id_pengajuan_pembelian' => 1, 'total_harga' => 240000, 'jum_item' => 1]);
        $this->assertDatabaseHas('barang', ['id' => 1, 'stok' => 9]);
        $this->assertDatabaseHas('barang', ['id' => 2, 'stok' => 0]);
        $this->assertDatabaseCount('barang_masuk_detail', 1);
        $this->getJson(route('pengajuan-pembelian.receiving', 1))->assertOk()
            ->assertJsonCount(2, 'items')->assertJsonPath('items.0.sisa', 6)->assertJsonPath('items.1.sisa', 20);
        $this->postJson(route('barang-masuk.store'), $this->payload('7'))->assertUnprocessable()->assertJsonValidationErrors('jumlah.0');
        $this->putJson(route('barang-masuk.update', $first->id), $this->payload('5'))->assertOk();
        $this->postJson(route('barang-masuk.store'), $this->payload('5'))->assertOk();
        $this->getJson(route('pengajuan-pembelian.receiving', 1))->assertOk()->assertJsonPath('items.0.sisa', 0);
        $this->postJson(route('barang-masuk.store'), $this->payload('1'))->assertUnprocessable();
        $this->assertDatabaseHas('barang', ['id' => 1, 'stok' => 15]);
        $this->deleteJson(route('barang-masuk.destroy', $first->id))->assertOk();
        $this->getJson(route('pengajuan-pembelian.receiving', 1))->assertOk()->assertJsonPath('items.0.sisa', 5);
    }

    public function test_purchase_rejects_invalid_lines_and_protects_referenced_master_data(): void
    {
        $this->enablePurchases();
        $payload = ['nomor' => 'PP-INVALID', 'tanggal' => '2026-11-01', 'id_supplier' => 1,
            'id_barang' => [1], 'jumlah' => ['0'], 'harga' => ['50.000']];
        foreach (['0', '-1', '1.5', '1000000001'] as $quantity) {
            $payload['jumlah'] = [$quantity];
            $this->postJson(route('pengajuan-pembelian.store'), $payload)->assertUnprocessable();
        }
        $payload['jumlah'] = ['2'];
        foreach (['-1', '1.5', '1000000001', 'abc'] as $price) {
            $payload['harga'] = [$price];
            $this->postJson(route('pengajuan-pembelian.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('harga.0');
        }
        $payload['harga'] = ['50.000'];
        $payload['id_barang'] = [1, 1]; $payload['jumlah'] = ['2', '3'];
        $this->postJson(route('pengajuan-pembelian.store'), $payload)->assertUnprocessable();
        foreach ([3 => 'barang.index', 4 => 'supplier.index'] as $id => $route) {
            DB::table('menu')->insert(['id' => $id, 'route_name' => $route]);
            DB::table('hak_akses')->insert(['id_user' => 1, 'id_menu' => $id]);
        }
        $this->deleteJson(route('barang.destroy', 1))->assertUnprocessable();
        $this->deleteJson(route('supplier.destroy', 1))->assertUnprocessable();
        $this->assertDatabaseCount('pengajuan_pembelian', 1);
        $this->assertDatabaseHas('barang', ['id' => 1, 'stok' => 5]);
        $this->assertDatabaseHas('supplier', ['id' => 1]);
    }

    public function test_receiving_requires_reference_and_matches_supplier_date_and_goods(): void
    {
        $payload = $this->payload(); unset($payload['id_pengajuan_pembelian']);
        $this->postJson(route('barang-masuk.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('id_pengajuan_pembelian');
        $payload = $this->payload(); $payload['tanggal'] = '2026-09-30';
        $this->postJson(route('barang-masuk.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('tanggal');
        DB::table('supplier')->insert(['id' => 2, 'nama' => 'Supplier Lain']);
        $payload = $this->payload(); $payload['id_supplier'] = 2;
        $this->postJson(route('barang-masuk.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('id_supplier');
        DB::table('barang')->insert(['id' => 2, 'nama' => 'Barang lain', 'id_satuan' => 1]);
        $payload = $this->payload(); $payload['id_barang'] = [2];
        $this->postJson(route('barang-masuk.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('id_barang.0');
        $this->assertDatabaseCount('barang_masuk', 0);
    }

    public function test_used_purchase_is_protected_and_purchase_permissions_are_enforced(): void
    {
        $this->enablePurchases();
        $id = $this->createIncoming();
        $this->deleteJson(route('pengajuan-pembelian.destroy', 1))->assertUnprocessable();
        $this->putJson(route('pengajuan-pembelian.update', 1), ['nomor' => 'PP-TEST', 'tanggal' => '2026-10-01',
            'id_supplier' => 1, 'id_barang' => [1], 'jumlah' => ['20']])->assertUnprocessable();
        $this->get(route('pengajuan-pembelian.show', 1))->assertOk()->assertSee('Diterima sebagian');
        $payload = $this->payload(); unset($payload['id_pengajuan_pembelian']);
        $this->putJson(route('barang-masuk.update', $id), $payload)->assertUnprocessable();
        DB::table('hak_akses')->where('id_menu', 2)->update(['lihat' => 0]);
        $this->get(route('pengajuan-pembelian.index'))->assertForbidden();
        $this->postJson(route('pengajuan-pembelian.store'), [])->assertForbidden();
        DB::table('hak_akses')->where('id_menu', 1)->update(['tambah' => 0]);
        $this->getJson(route('pengajuan-pembelian.receiving', 1))->assertForbidden();
    }

    public function test_legacy_receipt_remains_editable_without_new_reference(): void
    {
        $id = $this->createIncoming();
        DB::table('barang_masuk')->where('id', $id)->update(['id_pengajuan_pembelian' => null]);
        $payload = $this->payload('12'); unset($payload['id_pengajuan_pembelian']);
        $this->putJson(route('barang-masuk.update', $id), $payload)->assertOk();
        $this->get(route('barang-masuk.show', $id))->assertOk()->assertSee('Penerimaan lama tanpa referensi');
        $this->assertDatabaseHas('barang', ['id' => 1, 'stok' => 17]);
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
