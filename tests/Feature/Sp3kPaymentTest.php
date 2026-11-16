<?php

namespace Tests\Feature;

use App\Http\Controllers\PembayaranController;
use App\Models\Pemasukan;
use App\Services\Sp3kPlafonService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Sp3kPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-07 12:00:00'));
        Schema::create('akad', function (Blueprint $table) { $table->id(); $table->date('tgl_akad'); });
        Schema::create('detail_akad', function (Blueprint $table) {
            $table->id(); $table->integer('id_akad'); $table->integer('id_customer'); $table->integer('status');
        });
        DB::table('akad')->insert(['id' => 1, 'tgl_akad' => '2026-10-06']);
        DB::table('detail_akad')->insert(['id_akad' => 1, 'id_customer' => 1, 'status' => 2]);
        Schema::create('retensi', function (Blueprint $table) { $table->id(); });
        DB::table('retensi')->insert(['id' => 10]);
        Schema::create('customer', function (Blueprint $table) {
            $table->id();
            $table->integer('estimasi_plafon')->default(999);
        });
        Schema::create('piutang', function (Blueprint $table) {
            $table->string('jenis_piutang')->nullable();
            $table->id();
            $table->integer('id_customer');
            $table->integer('nominal');
        });
        Schema::create('wawancara', function (Blueprint $table) {
            $table->id();
            $table->integer('id_customer');
        });
        Schema::create('wawancara_sp3k', function (Blueprint $table) {
            $table->id();
            $table->integer('id_wawancara');
            $table->integer('acc_plafon');
            $table->integer('id_bank_kpr');
            $table->string('no_sp3k');
            $table->date('tgl_terbit_sp3k');
        });
        Schema::create('kategori_transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('kategori');
        });
        Schema::create('pemasukan', function (Blueprint $table) {
            $table->id();
            foreach (['id_customer', 'id_bank', 'id_piutang', 'id_kategori_transaksi', 'nominal', 'id_metode_bayar'] as $column) {
                $table->integer($column);
            }
            $table->date('tanggal');
            foreach (['no_kwitansi', 'keterangan', 'keterangan_kategori', 'lampiran'] as $column) {
                $table->string($column);
            }
        });
        Schema::create('pemasukan_retensi', function (Blueprint $table) {
            $table->id();
            $table->integer('id_pemasukan');
            $table->integer('id_retensi');
            $table->integer('nominal');
            $table->timestamps();
        });
        DB::table('kategori_transaksi')->insert(['id' => 9, 'kategori' => 'Pencairan KPR']);
        (require database_path('migrations/2026_10_07_010000_add_sp3k_snapshot_to_pemasukan.php'))->up();
        (require database_path('migrations/2026_11_17_020000_add_persentase_termin_to_pemasukan.php'))->up();
        DB::table('customer')->insert(['id' => 1]);
        DB::table('wawancara')->insert(['id' => 1, 'id_customer' => 1]);
        Route::post('/_test/kpr/{id}', [PembayaranController::class, 'tambahPencairanKpr']);
        Route::delete('/_test/kpr-payment/{id}', [PembayaranController::class, 'DeletePemasukan']);
    }

    private function sp3k(int $id = 1, int $amount = 300): void
    {
        DB::table('wawancara_sp3k')->insert([
            'id' => $id, 'id_wawancara' => 1, 'acc_plafon' => $amount,
            'id_bank_kpr' => 7, 'no_sp3k' => 'SP3K-' . $id, 'tgl_terbit_sp3k' => '2026-10-07',
        ]);
    }

    private function payload(): array
    {
        return ['id_sp3k' => 1, 'tanggal_pencairan' => '2026-10-07', 'jumlah_plafon' => '300',
            'jumlah_pencairan' => '270', 'persentase_termin' => '90'];
    }

    public function test_manual_estimate_cannot_replace_missing_sp3k(): void
    {
        $this->postJson('/_test/kpr/1', $this->payload())->assertStatus(422);
        $this->assertDatabaseCount('pemasukan', 0);
        $this->assertFalse(Route::has('Pembayaran.update-estimasi-plafon'));
    }

    public function test_disbursement_uses_sp3k_and_retains_snapshot_after_revision_or_deletion(): void
    {
        $this->sp3k();
        $this->postJson('/_test/kpr/1', $this->payload())->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('pemasukan', [
            'id_sp3k' => 1, 'no_sp3k' => 'SP3K-1', 'plafon_sp3k' => 300, 'nominal' => 270, 'id_bank_kpr_sp3k' => 7,
        ]);
        $this->assertDatabaseHas('pemasukan', ['id' => 1, 'persentase_termin' => 90]);
        $this->assertDatabaseCount('pemasukan_retensi', 0);
        $this->sp3k(2, 400);
        $this->assertEquals(400, app(Sp3kPlafonService::class)->latestForCustomer(1)->acc_plafon);
        DB::table('wawancara_sp3k')->where('id', 1)->delete();
        $this->assertEquals(300, Pemasukan::findOrFail(1)->plafon_sp3k);
        $this->assertSame('SP3K-1', Pemasukan::findOrFail(1)->no_sp3k);
    }

    public function test_stale_reference_wrong_plafon_and_inconsistent_percentage_are_rejected(): void
    {
        $this->sp3k();
        $payload = $this->payload();
        $payload['jumlah_plafon'] = '999';
        $payload['jumlah_pencairan'] = '969';
        $this->postJson('/_test/kpr/1', $payload)->assertStatus(422);
        $payload = $this->payload();
        $payload['persentase_termin'] = '80';
        $this->postJson('/_test/kpr/1', $payload)->assertStatus(422);
        $this->sp3k(2);
        $this->postJson('/_test/kpr/1', $this->payload())->assertStatus(422);
        $this->assertDatabaseCount('pemasukan', 0);
    }

    public function test_manual_percentage_disbursements_can_reach_full_plafon(): void
    {
        $this->sp3k();
        $this->postJson('/_test/kpr/1', $this->payload())->assertOk()->assertJson(['total_pencairan' => 270, 'sisa_plafon' => 30]);
        $second = $this->payload();
        $second['jumlah_pencairan'] = '20';
        $second['persentase_termin'] = '6.67';
        $this->postJson('/_test/kpr/1', $second)->assertOk()->assertJson(['total_pencairan' => 290, 'sisa_plafon' => 10]);
        $second['jumlah_pencairan'] = '10';
        $second['persentase_termin'] = '3.33';
        $this->postJson('/_test/kpr/1', $second)->assertOk()->assertJson(['total_pencairan' => 300, 'sisa_plafon' => 0, 'retensi_tersisa' => []]);
        $this->assertDatabaseCount('pemasukan', 3);
        $this->postJson('/_test/kpr/1', $second)->assertStatus(422);
        $this->assertEquals(300, DB::table('pemasukan')->sum('nominal'));
    }

    public function test_uncompleted_akad_and_dates_before_akad_are_rejected(): void
    {
        $this->sp3k();
        DB::table('detail_akad')->update(['status' => 1]);
        $this->postJson('/_test/kpr/1', $this->payload())->assertStatus(422);
        DB::table('detail_akad')->update(['status' => 2]);
        $payload = $this->payload();
        $payload['tanggal_pencairan'] = '2026-10-05';
        $this->postJson('/_test/kpr/1', $payload)->assertStatus(422);
        $this->assertDatabaseCount('pemasukan', 0);
    }

    public function test_total_above_plafon_and_negative_percentage_are_rejected(): void
    {
        $this->sp3k();
        $this->postJson('/_test/kpr/1', $this->payload())->assertOk();
        $payload = $this->payload();
        $payload['jumlah_pencairan'] = '31';
        $payload['persentase_termin'] = '10.33';
        $this->postJson('/_test/kpr/1', $payload)->assertStatus(422);
        $payload['jumlah_pencairan'] = '10';
        $payload['persentase_termin'] = '-1';
        $this->postJson('/_test/kpr/1', $payload)->assertStatus(422);
        $this->assertDatabaseCount('pemasukan', 1);
    }

    public function test_deletion_must_start_with_last_stage_and_restores_bank_balance(): void
    {
        $this->sp3k();
        $this->postJson('/_test/kpr/1', $this->payload())->assertOk();
        $second = $this->payload();
        $second['jumlah_pencairan'] = '20';
        $second['persentase_termin'] = '6.67';
        $this->postJson('/_test/kpr/1', $second)->assertOk();
        $this->deleteJson('/_test/kpr-payment/1')->assertStatus(422);
        $this->assertDatabaseCount('pemasukan', 2);
        $this->deleteJson('/_test/kpr-payment/2')->assertOk()->assertJson(['total_pencairan' => 270, 'sisa_plafon' => 30]);
        $summary = app(\App\Services\KprDisbursementService::class)->summary(1);
        $this->assertSame([], $summary['retensi_tersisa']);
        $this->assertSame(30, $summary['sisa_plafon']);
    }
    public function test_percentage_is_required_and_must_be_positive_at_most_100_with_two_decimals(): void
    {
        $this->sp3k();
        foreach ([null, '0', '-1', '101', '30.001', 'abc'] as $percentage) {
            $payload = $this->payload();
            $payload['persentase_termin'] = $percentage;
            $this->postJson('/_test/kpr/1', $payload)->assertUnprocessable()->assertJsonValidationErrors('persentase_termin');
        }
        $this->assertDatabaseCount('pemasukan', 0);
    }

    public function test_excel_30_20_40_10_pattern_without_retensi(): void
    {
        $this->sp3k();
        foreach ([30, 20, 40, 10] as $percentage) {
            $payload = $this->payload();
            $payload['persentase_termin'] = (string) $percentage;
            $payload['jumlah_pencairan'] = (string) ($percentage * 3);
            $this->postJson('/_test/kpr/1', $payload)->assertOk();
        }
        $this->assertSame(300, (int) DB::table('pemasukan')->sum('nominal'));
        $this->assertDatabaseCount('pemasukan_retensi', 0);
        $this->assertEquals([30, 20, 40, 10], DB::table('pemasukan')->orderBy('id')->pluck('persentase_termin')->all());
    }

}
