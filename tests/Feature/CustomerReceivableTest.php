<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Piutang;
use App\Services\CustomerReceivableService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerReceivableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('customer', function (Blueprint $t) {
            $t->id(); $t->string('nama_lengkap'); $t->integer('id_kavling')->nullable();
            $t->integer('stt_arsip')->default(0); $t->bigInteger('total_harga')->default(0);
            $t->date('tgl_booking')->nullable(); $t->date('tanggal_verif')->nullable();
        });
        Schema::create('kavling_peta', function (Blueprint $t) {
            $t->id(); $t->string('kode_kavling'); $t->text('rincian_biaya')->nullable();
            $t->bigInteger('hrg_jual')->default(0);
        });
        Schema::create('piutang', function (Blueprint $t) {
            $t->id(); $t->integer('id_customer'); $t->integer('id_bank'); $t->date('tanggal_piutang');
            $t->string('deskripsi'); $t->bigInteger('nominal'); $t->string('lampiran');
            $t->integer('status'); $t->bigInteger('terbayar'); $t->bigInteger('sisa_bayar');
            $t->date('tgl_pelunasan')->nullable(); $t->integer('id_kategori_transaksi');
        });
        Schema::create('pemasukan', function (Blueprint $t) {
            $t->id(); $t->integer('id_customer'); $t->integer('id_piutang')->default(0);
            $t->bigInteger('nominal'); $t->date('tanggal'); $t->string('keterangan')->nullable();
            $t->string('no_kwitansi')->nullable(); $t->string('lampiran')->nullable();
        });
        DB::table('kavling_peta')->insert(['id' => 1, 'kode_kavling' => 'A1', 'rincian_biaya' => json_encode([['nama' => 'Harga Rumah', 'nilai' => 700]])]);
    }

    public function test_capital_names_and_receivable_use_agreed_price_and_existing_payments(): void
    {
        $customer = Customer::create(['nama_lengkap' => 'Budi Santoso', 'id_kavling' => 1, 'total_harga' => 650]);
        $this->assertSame('BUDI SANTOSO', $customer->fresh()->nama_lengkap);
        DB::table('pemasukan')->insert(['id_customer' => $customer->id, 'nominal' => 100, 'tanggal' => '2026-10-01']);
        $service = app(CustomerReceivableService::class);
        $this->assertTrue($service->ensureForCustomer($customer->id));
        $this->assertFalse($service->ensureForCustomer($customer->id));
        $this->assertSame(1, Piutang::count());
        $this->assertDatabaseHas('piutang', ['id_customer' => $customer->id, 'nominal' => 650, 'terbayar' => 100, 'sisa_bayar' => 550]);
    }

    public function test_backfill_is_repeatable_skips_archives_and_preserves_existing_bills(): void
    {
        DB::table('customer')->insert([
            ['id' => 1, 'nama_lengkap' => 'Nama Lama', 'id_kavling' => 1, 'stt_arsip' => 0],
            ['id' => 2, 'nama_lengkap' => 'Arsip Lama', 'id_kavling' => 1, 'stt_arsip' => 1],
        ]);
        $this->artisan('customer:complete-receivables')->assertSuccessful();
        $this->assertDatabaseHas('customer', ['id' => 1, 'nama_lengkap' => 'NAMA LAMA']);
        $this->assertDatabaseHas('piutang', ['id_customer' => 1, 'nominal' => 700]);
        $this->assertDatabaseMissing('piutang', ['id_customer' => 2]);
        Piutang::first()->update(['nominal' => 600]);
        $this->artisan('customer:complete-receivables')->assertSuccessful();
        $this->assertSame(1, Piutang::count());
        $this->assertEquals(600, Piutang::first()->nominal);
    }
}
