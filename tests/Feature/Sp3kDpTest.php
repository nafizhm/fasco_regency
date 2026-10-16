<?php

namespace Tests\Feature;

use App\Http\Controllers\Transaksi\WawancaraController;
use App\Models\WawancaraSp3k;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Sp3kDpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('customer', function (Blueprint $table) {
            $table->id();
            $table->integer('id_status_progres')->nullable();
        });
        Schema::create('wawancara', function (Blueprint $table) {
            $table->id();
            $table->integer('id_customer');
            $table->integer('id_bank_kpr');
            $table->integer('status')->default(1);
        });
        Schema::create('wawancara_sp3k', function (Blueprint $table) {
            $table->id();
            foreach ((new WawancaraSp3k)->getFillable() as $field) {
                if (!str_starts_with($field, 'dp_')) $table->string($field)->nullable();
            }
        });
        (require database_path('migrations/2026_10_07_020000_add_dp_to_wawancara_sp3k.php'))->up();
        DB::table('customer')->insert(['id' => 1]);
        DB::table('wawancara')->insert(['id' => 1, 'id_customer' => 1, 'id_bank_kpr' => 7]);
        Route::post('/_test/sp3k-dp/{id}', [WawancaraController::class, 'simpanSp3k']);
    }

    protected function tearDown(): void
    {
        foreach (DB::table('wawancara_sp3k')->pluck('lampiran') as $filename) {
            $path = public_path('assets/SP3K/' . $filename);
            if ($filename && is_file($path)) unlink($path);
        }
        parent::tearDown();
    }

    private function payload(array $dp): array
    {
        return array_merge([
            'acc_plafon' => '589.000.000', 'tenor' => '15', 'tgl_terbit_sp3k' => '2026-10-07',
            'no_sp3k' => 'TEST-DP', 'id_notaris' => 1,
            'lampiran' => UploadedFile::fake()->create('sp3k.pdf', 1, 'application/pdf'),
        ], $dp);
    }

    public function test_percentage_is_calculated_from_approved_plafon_and_stored(): void
    {
        $this->postJson('/_test/sp3k-dp/1', $this->payload([
            'dp_mode' => 'persentase', 'dp_persen' => '10', 'dp_nilai' => 1,
        ]))->assertOk();
        $this->assertDatabaseHas('wawancara_sp3k', [
            'dp_mode' => 'persentase', 'dp_persen' => 10, 'dp_nilai' => 58900000, 'dp_dasar' => 589000000,
        ]);
    }

    public function test_nominal_mode_ignores_percentage_and_saves_nominal(): void
    {
        $this->postJson('/_test/sp3k-dp/1', $this->payload([
            'dp_mode' => 'nominal', 'dp_nominal' => '50.500.000', 'dp_persen' => '500',
        ]))->assertOk();
        $this->assertDatabaseHas('wawancara_sp3k', [
            'dp_mode' => 'nominal', 'dp_nilai' => 50500000, 'dp_persen' => null, 'dp_dasar' => null,
        ]);
    }

    public function test_decimal_percentage_and_zero_dp_are_supported(): void
    {
        $this->postJson('/_test/sp3k-dp/1', $this->payload([
            'dp_mode' => 'persentase', 'dp_persen' => '2,5',
        ]))->assertOk();
        $this->assertEquals(14725000, WawancaraSp3k::firstOrFail()->dp_nilai);
        $this->postJson('/_test/sp3k-dp/1', $this->payload([
            'dp_mode' => 'nominal', 'dp_nominal' => '0',
        ]))->assertOk();
        $this->assertEquals(0, WawancaraSp3k::orderByDesc('id')->firstOrFail()->dp_nilai);
    }

    public function test_invalid_mode_percentage_and_negative_nominal_are_rejected(): void
    {
        foreach ([['dp_mode' => 'invalid'], ['dp_mode' => 'persentase', 'dp_persen' => 101],
            ['dp_mode' => 'persentase', 'dp_persen' => -1], ['dp_mode' => 'nominal', 'dp_nominal' => '-10']] as $dp) {
            $this->postJson('/_test/sp3k-dp/1', $this->payload($dp))->assertStatus(422);
        }
        $this->assertDatabaseCount('wawancara_sp3k', 0);
        $this->assertFalse(Route::has('Pembayaran.update-sbum'));
    }

    private function prepareEdit(bool $allowed): void
    {
        Schema::create('menu', function (Blueprint $table) { $table->id(); $table->string('route_name'); });
        Schema::create('hak_akses', function (Blueprint $table) {
            $table->id(); $table->integer('id_user'); $table->integer('id_menu');
            $table->boolean('lihat'); $table->boolean('edit');
        });
        Schema::create('bank_kpr', function (Blueprint $table) { $table->id(); });
        Schema::create('notaris', function (Blueprint $table) { $table->id(); });
        Schema::create('log_aktivitas_pengguna', function (Blueprint $table) {
            $table->id(); $table->integer('id_user'); $table->string('user_name'); $table->text('aktivitas');
        });
        DB::table('menu')->insert(['id' => 1, 'route_name' => 'acc-bank.index']);
        DB::table('hak_akses')->insert(['id_user' => 1, 'id_menu' => 1, 'lihat' => 1, 'edit' => $allowed]);
        DB::table('bank_kpr')->insert(['id' => 7]);
        DB::table('notaris')->insert(['id' => 1]);
        $user = (new \App\Models\User)->forceFill(['id' => 1, 'username' => 'tester']);
        $this->actingAs($user);
        Route::put('/_test/sp3k-edit/{id}', [\App\Http\Controllers\Transaksi\AccBankController::class, 'update']);
    }

    public function test_edit_updates_plafon_and_dp_without_changing_disbursement_snapshot(): void
    {
        $this->postJson('/_test/sp3k-dp/1', $this->payload(['dp_mode' => 'nominal', 'dp_nominal' => '50.500.000']))->assertOk();
        $oldFile = WawancaraSp3k::firstOrFail()->lampiran;
        $this->prepareEdit(true);
        Schema::create('pemasukan', function (Blueprint $table) {
            $table->id(); $table->integer('id_sp3k'); $table->integer('plafon_sp3k'); $table->string('no_sp3k');
        });
        DB::table('pemasukan')->insert(['id_sp3k' => 1, 'plafon_sp3k' => 589000000, 'no_sp3k' => 'TEST-DP']);
        $this->putJson('/_test/sp3k-edit/1', [
            'acc_plafon' => '600.000.000', 'tenor' => 20, 'tgl_terbit_sp3k' => '2026-10-07',
            'no_sp3k' => 'REVISED', 'id_bank_kpr' => 7, 'id_notaris' => 1,
            'dp_mode' => 'persentase', 'dp_persen' => 10,
        ])->assertOk();
        $this->assertDatabaseHas('wawancara_sp3k', ['id' => 1, 'acc_plafon' => 600000000, 'dp_nilai' => 60000000, 'dp_dasar' => 600000000, 'no_sp3k' => 'REVISED']);
        $this->assertSame($oldFile, WawancaraSp3k::firstOrFail()->lampiran);
        $this->assertDatabaseHas('pemasukan', ['plafon_sp3k' => 589000000, 'no_sp3k' => 'TEST-DP']);
        $this->assertDatabaseHas('log_aktivitas_pengguna', ['id_user' => 1]);
    }

    public function test_edit_is_denied_without_edit_permission(): void
    {
        $this->postJson('/_test/sp3k-dp/1', $this->payload(['dp_mode' => 'nominal', 'dp_nominal' => '0']))->assertOk();
        $this->prepareEdit(false);
        $this->putJson('/_test/sp3k-edit/1', [])->assertForbidden();
        $this->assertDatabaseHas('wawancara_sp3k', ['id' => 1, 'acc_plafon' => 589000000]);
    }
}
