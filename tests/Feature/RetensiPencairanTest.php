<?php

namespace Tests\Feature;

use App\Http\Controllers\Keuangan\RetensiController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RetensiPencairanTest extends TestCase
{
    public function test_retensi_sums_kpr_disbursements_and_excludes_booking(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('customer', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lengkap');
            $table->integer('estimasi_plafon');
            $table->integer('id_lokasi')->nullable();
            $table->integer('id_kavling')->nullable();
        });
        Schema::create('lokasi_kavling', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kavling');
        });
        Schema::create('kavling_peta', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kavling');
        });
        Schema::create('kategori_transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('kategori');
        });
        Schema::create('pemasukan', function (Blueprint $table) {
            $table->id();
            $table->integer('id_customer');
            $table->integer('id_kategori_transaksi');
            $table->integer('nominal');
            $table->unsignedBigInteger('plafon_sp3k')->nullable();
        });
        Schema::create('retensi', function (Blueprint $table) {
            $table->id();
            $table->string('nama_retensi');
        });
        Schema::create('pemasukan_retensi', function (Blueprint $table) {
            $table->id();
            $table->integer('id_pemasukan');
            $table->integer('id_retensi');
            $table->integer('nominal');
        });
        DB::table('kategori_transaksi')->insert([
            ['id' => 4, 'kategori' => 'Booking'],
            ['id' => 9, 'kategori' => 'Pencairan KPR'],
        ]);
        DB::table('customer')->insert([
            ['id' => 1, 'nama_lengkap' => 'Customer KPR', 'estimasi_plafon' => 300000000],
            ['id' => 2, 'nama_lengkap' => 'Customer Booking', 'estimasi_plafon' => 100000000],
        ]);
        DB::table('retensi')->insert(['id' => 1, 'nama_retensi' => 'Retensi']);
        DB::table('pemasukan')->insert([
            ['id' => 1, 'id_customer' => 1, 'id_kategori_transaksi' => 4, 'nominal' => 2000000],
            ['id' => 2, 'id_customer' => 1, 'id_kategori_transaksi' => 9, 'nominal' => 227025000],
            ['id' => 3, 'id_customer' => 1, 'id_kategori_transaksi' => 9, 'nominal' => 50000000],
            ['id' => 4, 'id_customer' => 2, 'id_kategori_transaksi' => 4, 'nominal' => 1000000],
        ]);
        DB::table('pemasukan_retensi')->insert([
            ['id_pemasukan' => 2, 'id_retensi' => 1, 'nominal' => 20000000],
            ['id_pemasukan' => 3, 'id_retensi' => 1, 'nominal' => 2975000],
            ['id_pemasukan' => 1, 'id_retensi' => 1, 'nominal' => 999],
        ]);

        $data = app(RetensiController::class)->index()->getData();
        $this->assertCount(1, $data['rows']);
        $row = $data['rows']->first();
        $this->assertSame(1, $row['id']);
        $this->assertSame(277025000, $row['pencairan']);
        $this->assertSame(2975000, $row['retensi'][1]);
        $this->assertSame(2975000, $row['total_retensi']);
        $this->assertEquals(277025000, $data['totals']['pencairan']);
        $this->assertEquals(2975000, $data['totals']['retensi'][1]);
        $this->assertEquals(2975000, $data['totals']['total_retensi']);
    }
}
