<?php

namespace Tests\Feature;

use App\Models\{Customer, KavlingPeta, LokasiKavling, BankKPR, Wawancara, WawancaraSp3k, Pemasukan};
use App\Services\KprReceivableReport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class KprReceivableReportTest extends TestCase
{
    public function test_report_uses_bank_payments_latest_plafon_and_actual_stage_order(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        foreach ([new Customer, new KavlingPeta, new LokasiKavling, new BankKPR, new Wawancara, new WawancaraSp3k, new Pemasukan] as $model) {
            Schema::create($model->getTable(), function (Blueprint $t) use ($model) {
                $t->id();
                foreach (array_unique($model->getFillable()) as $field) {
                    if ($field !== 'id') $t->text($field)->nullable();
                }
            });
        }
        Schema::create('kategori_transaksi', function (Blueprint $t) { $t->id(); $t->string('kategori'); });
        Schema::create('akad', function (Blueprint $t) { $t->id(); $t->date('tgl_akad'); });
        Schema::create('detail_akad', function (Blueprint $t) {
            $t->id(); $t->integer('id_customer'); $t->integer('id_akad'); $t->integer('status');
        });
        DB::table('kategori_transaksi')->insert(['id' => 19, 'kategori' => 'Pencairan KPR']);
        DB::table('lokasi_kavling')->insert(['id' => 1, 'nama_kavling' => 'Proyek A']);
        DB::table('kavling_peta')->insert(['id' => 1, 'kode_kavling' => 'K.9']);
        DB::table('bank_kpr')->insert(['id' => 1, 'nama' => 'BRI']);
        DB::table('customer')->insert([
            ['id' => 1, 'nama_lengkap' => 'KPR', 'stt_arsip' => 0, 'jenis_pembelian' => 'KPR', 'id_lokasi' => 1, 'id_kavling' => 1],
            ['id' => 2, 'nama_lengkap' => 'Arsip', 'stt_arsip' => 1, 'jenis_pembelian' => 'KPR', 'id_lokasi' => 1, 'id_kavling' => 1],
            ['id' => 3, 'nama_lengkap' => 'Belum SP3K', 'stt_arsip' => 0, 'jenis_pembelian' => 'KPR', 'id_lokasi' => 1, 'id_kavling' => 1],
        ]);
        DB::table('wawancara')->insert(['id' => 1, 'id_customer' => 1]);
        DB::table('wawancara_sp3k')->insert([
            ['id' => 1, 'id_wawancara' => 1, 'acc_plafon' => 900, 'id_bank_kpr' => 1, 'tgl_terbit_sp3k' => '2026-01-01'],
            ['id' => 2, 'id_wawancara' => 1, 'acc_plafon' => 1000, 'id_bank_kpr' => 1, 'tgl_terbit_sp3k' => '2026-02-01'],
        ]);
        DB::table('pemasukan')->insert([
            ['id' => 1, 'id_customer' => 1, 'id_kategori_transaksi' => 19, 'nominal' => 200, 'tanggal' => '2026-04-01'],
            ['id' => 2, 'id_customer' => 1, 'id_kategori_transaksi' => 19, 'nominal' => 300, 'tanggal' => '2026-03-01'],
            ['id' => 3, 'id_customer' => 1, 'id_kategori_transaksi' => 1, 'nominal' => 100, 'tanggal' => '2026-03-01'],
        ]);
        DB::table('akad')->insert(['id' => 1, 'tgl_akad' => '2026-02-01']);
        DB::table('detail_akad')->insert(['id_customer' => 1, 'id_akad' => 1, 'status' => 2]);
        $rows = app(KprReceivableReport::class)->rows();
        $this->assertCount(2, $rows);
        $row = $rows->firstWhere('customer.id', 1);
        $this->assertSame(1000, $row['plafon']);
        $this->assertEquals(500, $row['sisa']);
        $this->assertSame('BRI', $row['bank']);
        $this->assertSame('2026-02-01', $row['akad']);
        $this->assertEquals([300, 200], $row['payments']->pluck('nominal')->all());
        $this->assertNull($rows->firstWhere('customer.id', 3)['sisa']);
    }
}
