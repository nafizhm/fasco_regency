<?php

namespace Tests\Feature;

use App\Http\Controllers\PembayaranController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PembayaranBalanceTest extends TestCase
{
    public function test_balance_uses_all_displayed_payments_and_ignores_plafon_and_bank_dp(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('customer', function (Blueprint $table) {
            $table->id();
            $table->integer('estimasi_plafon');
            $table->integer('sbum');
        });
        Schema::create('piutang', function (Blueprint $table) {
            $table->id();
            $table->integer('id_customer');
            $table->integer('nominal');
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
            $table->string('keterangan')->nullable();
            $table->string('keterangan_kategori')->nullable();
        });
        DB::table('customer')->insert(['id' => 1, 'estimasi_plafon' => 589000000, 'sbum' => 50500000]);
        DB::table('piutang')->insert(['id_customer' => 1, 'nominal' => 655000000]);
        DB::table('kategori_transaksi')->insert([
            ['id' => 4, 'kategori' => 'Booking'],
            ['id' => 9, 'kategori' => 'Pencairan KPR'],
        ]);
        DB::table('pemasukan')->insert([
            ['id_customer' => 1, 'id_kategori_transaksi' => 4, 'nominal' => 2000000, 'keterangan' => 'Booking Fee'],
            ['id_customer' => 1, 'id_kategori_transaksi' => 9, 'nominal' => 277025000, 'keterangan' => 'Pencairan bank'],
            ['id_customer' => 1, 'id_kategori_transaksi' => 4, 'nominal' => 1000000, 'keterangan' => 'Pencairan KPR'],
            ['id_customer' => 1, 'id_kategori_transaksi' => 4, 'nominal' => 3000000, 'keterangan' => 'Biaya ganti nama customer'],
        ]);
        $controller = app(PembayaranController::class);
        $balance = new \ReflectionMethod($controller, 'getSisaBayarCustomer');
        $paid = new \ReflectionMethod($controller, 'getJumlahBayarCustomer');
        $this->assertEquals(280025000, $paid->invoke($controller, 1));
        $this->assertEquals(374975000, $balance->invoke($controller, 1));

        DB::table('customer')->where('id', 1)->update(['estimasi_plafon' => 0, 'sbum' => 0]);
        $this->assertEquals(374975000, $balance->invoke($controller, 1));

        DB::table('pemasukan')->insert(['id_customer' => 1, 'id_kategori_transaksi' => 4, 'nominal' => 400000000, 'keterangan' => 'Pelunasan']);
        $this->assertEquals(0, $balance->invoke($controller, 1));
    }
}
