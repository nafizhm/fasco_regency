<?php

namespace Tests\Feature;

use App\Http\Controllers\Keuangan\PengeluaranController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PengeluaranManualAccountTest extends TestCase
{
    public function test_expense_can_be_created_and_edited_with_manual_destination_account(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('kategori_transaksi', function (Blueprint $t) {
            $t->id(); $t->string('kategori'); $t->string('jenis_kategori');
        });
        Schema::create('pengeluaran', function (Blueprint $t) {
            $t->id();
            foreach (['id_hutang', 'id_piutang', 'id_po', 'id_mutasi', 'id_bank', 'id_metode_bayar', 'id_kategori_transaksi'] as $field) $t->integer($field)->default(0);
            foreach (['no_tanda_terima', 'diterima_dari', 'nama_penerima', 'keterangan', 'lampiran'] as $field) $t->string($field)->nullable();
            $t->date('tanggal'); $t->integer('nominal');
        });
        (require database_path('migrations/2026_11_18_010000_add_destination_account_to_pengeluaran.php'))->up();
        DB::table('kategori_transaksi')->insert(['id' => 1, 'kategori' => 'Umum', 'jenis_kategori' => 'PENGELUARAN']);
        DB::table('kategori_transaksi')->insert(['id' => 2, 'kategori' => 'Pembayaran Hutang', 'jenis_kategori' => 'PENGELUARAN']);
        Schema::create('hutang', function (Blueprint $t) {
            $t->id(); $t->integer('nominal'); $t->integer('terbayar')->default(0);
            $t->integer('sisa_bayar'); $t->integer('status')->default(1);
            $t->dateTime('tgl_pelunasan')->nullable();
        });
        DB::table('hutang')->insert(['id' => 1, 'nominal' => 200000, 'sisa_bayar' => 200000]);
        Route::post('/_test/expense-manual', [PengeluaranController::class, 'store']);
        Route::put('/_test/expense-manual/{id}', [PengeluaranController::class, 'update']);
        $payload = [
            'tanggal' => '2026-11-18', 'no_tanda_terima' => 'TEST-MANUAL',
            'diterima_dari' => 'Admin', 'nama_penerima' => 'Penerima',
            'nominal' => '150.000', 'id_metode_bayar' => 2, 'id_kategori_transaksi' => 1,
            'nama_bank' => 'Bank Tujuan', 'rekening_tujuan' => '001234567890',
        ];
        try {
            $this->postJson('/_test/expense-manual', $payload + [
                'lampiran' => UploadedFile::fake()->create('bukti.pdf', 1, 'application/pdf'),
            ])->assertOk();
            $this->assertDatabaseHas('pengeluaran', [
                'id' => 1, 'id_bank' => 0, 'nama_bank' => 'Bank Tujuan', 'rekening_tujuan' => '001234567890',
            ]);
            $payload['nama_bank'] = 'Bank Koreksi';
            $payload['rekening_tujuan'] = '000987654321';
            $this->putJson('/_test/expense-manual/1', $payload)->assertOk();
            $this->assertDatabaseHas('pengeluaran', ['id' => 1, 'nama_bank' => 'Bank Koreksi', 'rekening_tujuan' => '000987654321']);

            $payload['no_tanda_terima'] = 'TEST-HUTANG';
            $payload['id_kategori_transaksi'] = 2;
            $payload['id_hutang'] = 1;
            $this->postJson('/_test/expense-manual', $payload + [
                'lampiran' => UploadedFile::fake()->create('hutang.pdf', 1, 'application/pdf'),
            ])->assertOk();
            $this->assertDatabaseHas('pengeluaran', [
                'id_hutang' => 1, 'id_bank' => 0, 'nama_bank' => 'Bank Koreksi', 'rekening_tujuan' => '000987654321',
            ]);
            $this->assertDatabaseHas('hutang', ['id' => 1, 'terbayar' => 150000, 'sisa_bayar' => 50000]);
        } finally {
            foreach (DB::table('pengeluaran')->pluck('lampiran') as $filename) {
                if ($filename && is_file($path = public_path('assets/keuangan/pengeluaran/' . $filename))) unlink($path);
            }
        }
    }
}
