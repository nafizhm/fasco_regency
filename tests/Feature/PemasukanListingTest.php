<?php

namespace Tests\Feature;

use App\Http\Controllers\Keuangan\PemasukanController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PemasukanListingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('menu', function (Blueprint $table) {
            $table->id();
            $table->string('route_name');
        });
        Schema::create('bank', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
        });
        Schema::create('kategori_transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('kategori');
        });
        Schema::create('pemasukan', function (Blueprint $table) {
            $table->id();
            $table->integer('id_customer')->nullable();
            $table->integer('id_hutang')->default(0);
            $table->integer('id_mutasi')->default(0);
            $table->integer('id_bank')->default(1);
            $table->integer('id_kategori_transaksi')->default(1);
            $table->integer('nominal')->default(100000);
            $table->date('tanggal');
            $table->string('keterangan');
        });
        DB::table('bank')->insert(['id' => 1, 'nama' => 'Bank Test']);
        DB::table('kategori_transaksi')->insert(['id' => 1, 'kategori' => 'Pemasukan']);
        foreach ([null, 0, 7, 8] as $index => $customer) {
            DB::table('pemasukan')->insert([
                'id' => $index + 1, 'id_customer' => $customer,
                'tanggal' => '2026-11-18', 'keterangan' => 'Transaksi test',
            ]);
        }
        Route::get('/_test/pemasukan-list', [PemasukanController::class, 'index']);
    }

    public function test_customer_payments_are_excluded_from_income_list_and_counts(): void
    {
        $response = $this->getJson('/_test/pemasukan-list', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('recordsTotal', 2)->assertJsonPath('recordsFiltered', 2);
        $this->assertEqualsCanonicalizing([1, 2], array_column($response->json('data'), 'id'));
        $this->assertDatabaseCount('pemasukan', 4);
    }

    public function test_search_and_date_filter_do_not_reintroduce_customer_payments(): void
    {
        $this->getJson('/_test/pemasukan-list?filter_tanggal=2026-11-18&search[value]=Bank', [
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk()->assertJsonPath('recordsFiltered', 2)->assertJsonCount(2, 'data');
    }
}
