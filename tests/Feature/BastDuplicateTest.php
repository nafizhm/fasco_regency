<?php

namespace Tests\Feature;

use App\Http\Controllers\Transaksi\BastController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BastDuplicateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('customer', function (Blueprint $t) {
            $t->id(); $t->string('nama_lengkap'); $t->integer('id_lokasi')->nullable();
        });
        Schema::create('lokasi_kavling', fn (Blueprint $t) => $t->id());
        Schema::create('bast', function (Blueprint $t) {
            $t->id(); $t->integer('id_customer')->nullable(); $t->date('tanggal_bast'); $t->string('no_bast');
        });
        Schema::create('menu', function (Blueprint $t) { $t->id(); $t->string('route_name'); });
        DB::table('customer')->insert([
            ['id' => 1, 'nama_lengkap' => 'SUDAH BAST'],
            ['id' => 2, 'nama_lengkap' => 'BELUM BAST'],
        ]);
        DB::table('bast')->insert(['id_customer' => 1, 'tanggal_bast' => '2026-10-01', 'no_bast' => 'BAST-001']);
        Route::post('/_test/bast', [BastController::class, 'store']);
        Route::get('/_test/bast-options', function (\Illuminate\Http\Request $request) {
            $view = app(BastController::class)->index($request);
            return response()->json($view->getData()['customerList']->pluck('id'));
        })->name('test.bast-options');
    }

    public function test_picker_excludes_customers_who_already_have_bast(): void
    {
        $this->getJson('/_test/bast-options')->assertOk()->assertExactJson([2]);
        DB::table('bast')->delete();
        $this->getJson('/_test/bast-options')->assertOk()->assertExactJson([2, 1]);
    }

    public function test_duplicate_submission_from_an_old_form_is_rejected(): void
    {
        $this->postJson('/_test/bast', ['id_customer' => 1, 'tanggal_bast' => '2026-11-01'])
            ->assertUnprocessable()->assertJsonValidationErrors('id_customer');
        $this->assertDatabaseCount('bast', 1);
    }

    public function test_nonexistent_customer_is_rejected(): void
    {
        $this->postJson('/_test/bast', ['id_customer' => 999, 'tanggal_bast' => '2026-11-01'])
            ->assertUnprocessable()->assertJsonValidationErrors('id_customer');
        $this->assertDatabaseCount('bast', 1);
    }
}
