<?php

namespace Tests\Feature;

use App\Http\Controllers\PembayaranController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerIncomeCategoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('kategori_transaksi', function (Blueprint $t) {
            $t->id(); $t->string('kategori'); $t->string('jenis_kategori');
        });
        Schema::create('customer', function (Blueprint $t) { $t->id(); $t->integer('id_lokasi')->nullable(); });
        Schema::create('lokasi_kavling', fn (Blueprint $t) => $t->id());
        Schema::create('piutang', function (Blueprint $t) { $t->id(); $t->integer('id_customer'); $t->integer('nominal'); });
        Schema::create('pemasukan', function (Blueprint $t) {
            $t->id();
            foreach (['tanggal','id_customer','id_bank','id_piutang','id_kategori_transaksi','no_kwitansi','nominal','keterangan','keterangan_kategori','id_metode_bayar','lampiran'] as $field) $t->string($field)->nullable();
        });
        DB::table('customer')->insert(['id' => 1]);
        DB::table('kategori_transaksi')->insert([
            ['id' => 99, 'kategori' => 'Pemasukan Baru', 'jenis_kategori' => 'PEMASUKAN'],
            ['id' => 5, 'kategori' => 'Biaya Kantor', 'jenis_kategori' => 'PENGELUARAN'],
        ]);
        Route::post('/_test/customer-income', fn (\Illuminate\Http\Request $r) => app(PembayaranController::class)->tambahPemasukan($r, 1));
    }

    private function payload(int $category): array
    {
        return ['tanggal_pembayaran' => '2026-11-05', 'id_kategori_transaksi' => $category,
            'id_bank' => 1, 'id_metode_bayar' => 1, 'nominal_bayar' => '100.000',
            'keterangan_pembayaran' => 'Pembayaran customer', 'no_kwitansi' => 'TEST-INCOME'];
    }

    public function test_new_income_category_is_accepted_and_its_master_name_is_saved(): void
    {
        $this->postJson('/_test/customer-income', $this->payload(99))->assertOk();
        $this->assertDatabaseHas('pemasukan', ['id_kategori_transaksi' => 99, 'keterangan_kategori' => 'Pemasukan Baru', 'nominal' => 100000]);
    }

    public function test_expense_and_unknown_categories_are_rejected(): void
    {
        foreach ([5, 1000] as $category) {
            $this->postJson('/_test/customer-income', $this->payload($category))
                ->assertUnprocessable()->assertJsonValidationErrors('id_kategori_transaksi');
        }
        $this->assertDatabaseCount('pemasukan', 0);
    }

    public function test_transfer_payment_can_be_saved_without_attachment(): void
    {
        $payload = $this->payload(99);
        $payload['id_metode_bayar'] = 2;
        $this->postJson('/_test/customer-income', $payload)->assertOk();
        $this->assertDatabaseHas('pemasukan', ['id_metode_bayar' => 2, 'lampiran' => '', 'nominal' => 100000]);
    }
}
