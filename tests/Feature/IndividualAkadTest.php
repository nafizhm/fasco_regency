<?php

namespace Tests\Feature;

use App\Http\Controllers\Transaksi\AkadController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Route, Schema};
use Tests\TestCase;

class IndividualAkadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('sqlite', config('database.default'));
        Schema::create('customer', function (Blueprint $t) {
            $t->id(); $t->integer('id_status_progres')->default(7);
            $t->string('nama_lengkap')->nullable(); $t->string('nik')->nullable(); $t->string('no_telp')->nullable();
            $t->text('alamat_ktp')->nullable(); $t->date('tgl_booking')->nullable();
            $t->integer('id_bank_kpr')->nullable(); $t->integer('id_marketing')->nullable();
        });
        Schema::create('bank_kpr', function (Blueprint $t) { $t->id(); $t->string('nama'); });
        Schema::create('marketing_offline', function (Blueprint $t) { $t->id(); });
        Schema::create('akad', function (Blueprint $t) {
            $t->id(); $t->date('tgl_akad')->nullable(); $t->string('keterangan');
        });
        Schema::create('detail_akad', function (Blueprint $t) {
            $t->id(); $t->integer('id_akad'); $t->integer('id_customer');
            $t->string('status');
            $t->string('jenis_akad'); $t->string('keterangan');
        });
        DB::table('customer')->insert([['id' => 1], ['id' => 2]]);
        Route::post('/test-akad', [AkadController::class, 'store']);
        Route::put('/test-akad/{id}', [AkadController::class, 'update']);
        Route::delete('/test-akad/{id}', [AkadController::class, 'destroy']);
        Route::get('/test-akad/{id}/edit', [AkadController::class, 'edit']);
    }

    private function input(array $changes = []): array
    {
        return array_merge(['id_customer' => 1, 'tgl_akad' => now('Asia/Jakarta')->toDateString(), 'jenis_akad' => 'AJB'], $changes);
    }

    public function test_akad_edit_updates_buyer_bank_and_historical_dates_atomically(): void
    {
        DB::table('bank_kpr')->insert(['id' => 1, 'nama' => 'Bank KPR']);
        $this->postJson('/test-akad', $this->input())->assertOk();
        $buyer = ['nama_lengkap' => 'Pembeli', 'nik' => null, 'no_telp' => '08123456789',
            'tgl_booking' => '2024-01-02', 'id_bank_kpr' => 1];
        $this->putJson('/test-akad/1', $this->input(['buyer' => $buyer, 'tgl_akad' => '2024-02-01']))->assertOk();
        $this->assertDatabaseHas('customer', ['id' => 1, 'nama_lengkap' => 'Pembeli', 'id_bank_kpr' => 1, 'tgl_booking' => '2024-01-02']);
        $this->getJson('/test-akad/1/edit')->assertOk()->assertJsonPath('data.buyer.id_bank_kpr', 1);
        $buyer['tgl_booking'] = '2024-03-01';
        $buyer['nama_lengkap'] = 'Tidak tersimpan';
        $this->putJson('/test-akad/1', $this->input(['buyer' => $buyer, 'tgl_akad' => '2024-02-01']))->assertUnprocessable();
        $this->assertDatabaseHas('customer', ['id' => 1, 'nama_lengkap' => 'Pembeli']);
    }

    public function test_imported_akad_with_unknown_date_can_be_edited_then_completed(): void
    {
        DB::table('akad')->insert(['id' => 1, 'tgl_akad' => null, 'keterangan' => 'Historis']);
        DB::table('detail_akad')->insert(['id' => 1, 'id_akad' => 1, 'id_customer' => 1, 'status' => 2, 'jenis_akad' => 'AJB', 'keterangan' => '']);
        $this->putJson('/test-akad/1', $this->input(['tgl_akad' => null, 'buyer' => ['nama_lengkap' => 'Kantor']]))->assertOk();
        $this->assertDatabaseHas('akad', ['id' => 1, 'tgl_akad' => null]);
        $this->putJson('/test-akad/1', $this->input(['tgl_akad' => '2024-01-01']))->assertOk();
        $this->assertDatabaseHas('akad', ['id' => 1, 'tgl_akad' => '2024-01-01']);
    }

    public function test_crud_records_completed_akad_without_documents_or_attendance(): void
    {
        $this->postJson('/test-akad', $this->input())->assertOk();
        $this->assertDatabaseHas('detail_akad', ['id' => 1, 'id_customer' => 1, 'status' => 2]);
        $this->assertDatabaseHas('customer', ['id' => 1, 'id_status_progres' => 3]);
        $this->getJson('/test-akad/1/edit')->assertOk()->assertJsonPath('data.id_customer', 1);
        $this->putJson('/test-akad/1', $this->input(['id_customer' => 2, 'jenis_akad' => 'PPJB']))->assertOk();
        $this->assertDatabaseHas('customer', ['id' => 1, 'id_status_progres' => 7]);
        $this->assertDatabaseHas('detail_akad', ['id_customer' => 2, 'jenis_akad' => 'PPJB']);
        $this->deleteJson('/test-akad/1')->assertOk();
        $this->assertDatabaseCount('detail_akad', 0);
        $this->assertDatabaseCount('akad', 0);
        $this->assertDatabaseHas('customer', ['id' => 2, 'id_status_progres' => 7]);
    }

    public function test_duplicate_buyers_future_dates_and_multiple_buyers_are_rejected(): void
    {
        $this->postJson('/test-akad', $this->input())->assertOk();
        $this->postJson('/test-akad', $this->input())->assertUnprocessable()->assertJsonValidationErrors('id_customer');
        $this->postJson('/test-akad', $this->input(['id_customer' => [1, 2]]))->assertUnprocessable();
        $this->postJson('/test-akad', $this->input(['id_customer' => 2, 'tgl_akad' => now('Asia/Jakarta')->addDay()->toDateString()]))->assertUnprocessable()->assertJsonValidationErrors('tgl_akad');
        $this->assertDatabaseCount('akad', 1);
    }

    public function test_editing_legacy_group_does_not_change_other_buyers(): void
    {
        DB::table('akad')->insert(['id' => 1, 'tgl_akad' => '2025-01-01', 'keterangan' => 'Lama']);
        foreach ([1, 2] as $buyer) {
            DB::table('detail_akad')->insert(['id_akad' => 1, 'id_customer' => $buyer, 'status' => 1, 'jenis_akad' => '', 'keterangan' => '']);
        }
        $this->putJson('/test-akad/1', $this->input())->assertOk();
        $this->assertDatabaseHas('akad', ['id' => 1, 'tgl_akad' => '2025-01-01']);
        $this->assertDatabaseHas('detail_akad', ['id' => 2, 'id_akad' => 1, 'status' => 1]);
        $this->assertDatabaseHas('detail_akad', ['id' => 1, 'id_akad' => 2, 'status' => 2]);
        $this->deleteJson('/test-akad/2')->assertOk();
        $this->assertDatabaseHas('akad', ['id' => 2]);
    }
}
