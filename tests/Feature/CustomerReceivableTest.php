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
    public function test_migration_splits_legacy_dp_and_recalculates_when_sp3k_changes(): void
    {
        Schema::table('customer', function (Blueprint $t) {
            if (!Schema::hasColumn('customer', 'jenis_pembelian')) $t->string('jenis_pembelian')->nullable();
            if (!Schema::hasColumn('customer', 'dp_kpr')) $t->integer('dp_kpr')->nullable();
        });
        if (!Schema::hasColumn('pemasukan', 'id_kategori_transaksi')) Schema::table('pemasukan', fn (Blueprint $t) => $t->integer('id_kategori_transaksi')->default(1));
        if (!Schema::hasTable('kategori_transaksi')) Schema::create('kategori_transaksi', function (Blueprint $t) { $t->id(); $t->string('kategori'); });
        DB::table('kategori_transaksi')->insert(['id' => 9, 'kategori' => 'Pencairan KPR']);
        Schema::create('wawancara', function (Blueprint $t) { $t->id(); $t->integer('id_customer'); });
        Schema::create('wawancara_sp3k', function (Blueprint $t) {
            $t->id(); $t->integer('id_wawancara'); $t->integer('acc_plafon'); $t->date('tgl_terbit_sp3k');
        });
        $c = Customer::create(['nama_lengkap' => 'KPR', 'id_kavling' => 1, 'total_harga' => 700,
            'jenis_pembelian' => 'KPR', 'dp_kpr' => 200]);
        app(CustomerReceivableService::class)->ensureForCustomer($c->id);
        $billId = Piutang::first()->id;
        DB::table('wawancara')->insert(['id' => 1, 'id_customer' => $c->id]);
        DB::table('wawancara_sp3k')->insert(['id' => 1, 'id_wawancara' => 1, 'acc_plafon' => 600, 'tgl_terbit_sp3k' => '2026-11-17']);
        DB::table('pemasukan')->insert([
            ['id_customer' => $c->id, 'id_kategori_transaksi' => 1, 'nominal' => 30, 'tanggal' => '2026-11-17'],
            ['id_customer' => $c->id, 'id_kategori_transaksi' => 9, 'nominal' => 540, 'tanggal' => '2026-11-17'],
        ]);
        if (Schema::hasColumn('piutang', 'jenis_piutang')) Schema::table('piutang', fn (Blueprint $t) => $t->dropColumn('jenis_piutang'));
        (require database_path('migrations/2026_11_17_010000_split_kpr_receivables.php'))->up();
        $this->assertDatabaseHas('piutang', ['id' => $billId, 'jenis_piutang' => 'dp', 'nominal' => 100, 'terbayar' => 30, 'sisa_bayar' => 70]);
        $this->assertDatabaseHas('piutang', ['jenis_piutang' => 'bank', 'nominal' => 600, 'terbayar' => 540, 'sisa_bayar' => 60]);
        app(\App\Services\PiutangPaymentService::class)->syncCustomer($c->id);
        $this->assertSame(2, Piutang::count());
        \App\Models\WawancaraSp3k::find(1)->update(['acc_plafon' => 550]);
        $this->assertDatabaseHas('piutang', ['id' => $billId, 'nominal' => 150, 'sisa_bayar' => 120]);
        $this->assertDatabaseHas('piutang', ['jenis_piutang' => 'bank', 'nominal' => 550, 'sisa_bayar' => 10]);
    }

}
