<?php

namespace Tests\Feature;

use App\Http\Controllers\Keuangan\PemasukanController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PemasukanWithoutAccountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('customer', fn (Blueprint $t) => $t->id());
        Schema::create('kategori_transaksi', function (Blueprint $t) {
            $t->id(); $t->string('kategori'); $t->string('jenis_kategori');
        });
        Schema::create('piutang', function (Blueprint $t) {
            $t->id(); $t->integer('id_customer')->default(0);
            $t->integer('nominal'); $t->integer('terbayar')->default(0);
            $t->integer('sisa_bayar'); $t->integer('status')->default(1);
            $t->date('tgl_pelunasan')->nullable();
        });
        Schema::create('pemasukan', function (Blueprint $t) {
            $t->id();
            foreach (['id_bank', 'id_piutang', 'id_customer', 'id_lokasi', 'id_hutang', 'id_invoice', 'id_mutasi', 'id_kategori_transaksi'] as $field) {
                $t->integer($field)->default(0);
            }
            $t->integer('nominal'); $t->date('tanggal');
            $t->text('keterangan')->nullable(); $t->string('lampiran')->nullable();
            $t->string('no_kwitansi')->nullable();
        });
        DB::table('kategori_transaksi')->insert([
            ['id' => 1, 'kategori' => 'Pemasukan Umum', 'jenis_kategori' => 'PEMASUKAN'],
            ['id' => 2, 'kategori' => 'Pembayaran Piutang', 'jenis_kategori' => 'PEMASUKAN'],
        ]);
        DB::table('piutang')->insert(['id' => 1, 'nominal' => 200000, 'sisa_bayar' => 200000]);
        Route::post('/_test/income-no-account', [PemasukanController::class, 'store']);
        Route::put('/_test/income-no-account/{id}', [PemasukanController::class, 'update']);
    }

    protected function tearDown(): void
    {
        foreach (DB::table('pemasukan')->pluck('lampiran') as $filename) {
            if ($filename && is_file($path = public_path('assets/keuangan/pemasukan/' . $filename))) unlink($path);
        }
        parent::tearDown();
    }

    public function test_general_income_and_receivable_payment_save_without_account(): void
    {
        foreach ([1, 2] as $category) {
            $this->postJson('/_test/income-no-account', [
                'tanggal' => '2026-11-18', 'nominal' => '100.000',
                'id_kategori_transaksi' => $category,
                'id_piutang' => $category === 2 ? 1 : null,
                'lampiran' => UploadedFile::fake()->create('bukti.pdf', 1, 'application/pdf'),
            ])->assertOk();
            $this->assertDatabaseHas('pemasukan', ['id_kategori_transaksi' => $category, 'id_bank' => 0, 'nominal' => 100000]);
        }
        $this->assertDatabaseHas('piutang', ['id' => 1, 'terbayar' => 100000, 'sisa_bayar' => 100000]);

        DB::table('pemasukan')->where('id', 1)->update(['id_bank' => 9]);
        $this->putJson('/_test/income-no-account/1', [
            'tanggal' => '2026-11-18', 'nominal' => '150.000', 'keterangan' => 'Koreksi',
        ])->assertOk();
        $this->assertDatabaseHas('pemasukan', ['id' => 1, 'id_bank' => 9, 'nominal' => 150000]);
    }
}
