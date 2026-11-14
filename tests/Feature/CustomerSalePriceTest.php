<?php

namespace Tests\Feature;

use App\Http\Controllers\Customer\CustomerController;
use App\Models\{Customer, CustomerTempo, PengajuanHold, Piutang, Pemasukan, User};
use App\Services\CustomerSalePrice;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Route, Schema};
use Tests\TestCase;

class CustomerSalePriceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        foreach ([new Customer, new CustomerTempo, new PengajuanHold, new Piutang, new Pemasukan] as $model) {
            Schema::create($model->getTable(), function (Blueprint $t) use ($model) {
                $t->id();
                foreach ($model->getFillable() as $field) {
                    if ($field !== 'id') $t->text($field)->nullable();
                }
            });
        }
        Schema::create('kavling_peta', function (Blueprint $t) {
            $t->id(); $t->string('kode_kavling'); $t->text('rincian_biaya');
        });
        Schema::create('sppr', function (Blueprint $t) { $t->id(); $t->integer('id_customer'); $t->integer('harga_jual'); });
        DB::table('sppr')->insert(['id_customer' => 1, 'harga_jual' => 150000000]);
        Schema::create('log_aktivitas_pengguna', function (Blueprint $t) {
            $t->id(); $t->integer('id_user'); $t->string('user_name'); $t->text('aktivitas');
        });
        DB::table('kavling_peta')->insert(['id' => 1, 'kode_kavling' => 'A1', 'rincian_biaya' => json_encode([
            ['nama' => 'Harga Rumah', 'nilai' => 150000000], ['nama' => 'Biaya Surat', 'nilai' => 5000000],
        ])]);
        Customer::create(['id_kavling' => 1, 'nama_lengkap' => 'CUSTOMER', 'nik' => '123', 'id_status_progres' => 3,
            'hrg_jual' => 150000000, 'harga_jual_awal' => 150000000, 'diskon' => 0, 'total_harga' => 155000000]);
        PengajuanHold::create(['id_kavling' => 1, 'nik' => '123', 'stt_reg' => 2, 'diskon' => 0, 'total_harga' => 155000000]);
        Piutang::create(['id_customer' => 1, 'deskripsi' => 'Harga Rumah Blok A1', 'nominal' => 150000000]);
        Piutang::create(['id_customer' => 1, 'deskripsi' => 'Biaya Surat', 'nominal' => 5000000]);
        Piutang::create(['id_customer' => 1, 'deskripsi' => 'Tambahan Kanopi', 'nominal' => 20000000]);
        Pemasukan::create(['id_customer' => 1, 'nominal' => 100000000, 'tanggal' => '2026-10-01']);
        $this->actingAs((new User)->forceFill(['id' => 1, 'id_role' => 1, 'username' => 'tester']));
        Route::put('/_test/customer-sale/{id}', [CustomerController::class, 'update']);
        Route::put('/_test/customer-house-bill/{id}', [\App\Http\Controllers\PembayaranController::class, 'updateTagihan']);
        Route::put('/_test/customer-master-price/{id}', [\App\Http\Controllers\PembayaranController::class, 'UpdateHargaRumah']);
        Route::post('/_test/customer-sale-approve/{id}', [CustomerController::class, 'postTempo']);
    }

    private function payload(): array
    {
        return ['nama_lengkap' => 'Customer Baru', 'nik' => '123', 'harga_jual_awal' => '160.000.000', 'diskon' => '20.000.000'];
    }

    public function test_kpr_price_edit_does_not_increase_dp_receivable(): void
    {
        DB::table('customer')->where('id', 1)->update(['jenis_pembelian' => 'KPR', 'dp_kpr' => 30000000]);
        DB::table('piutang')->where('id', 1)->update(['deskripsi' => 'DP KPR Blok A1', 'nominal' => 30000000]);
        $this->putJson('/_test/customer-sale/1', $this->payload())->assertOk();
        $this->assertDatabaseHas('customer', ['id' => 1, 'dp_kpr' => 30000000, 'total_harga' => 145000000]);
        $this->assertDatabaseHas('piutang', ['id' => 1, 'nominal' => 30000000, 'deskripsi' => 'DP KPR Blok A1']);
        $this->putJson('/_test/customer-house-bill/1', ['nominal' => '50.000.000'])->assertUnprocessable();
    }

    public function test_edit_updates_sale_and_receivables_preserves_payments_and_is_repeatable(): void
    {
        for ($i = 0; $i < 2; $i++) $this->putJson('/_test/customer-sale/1', $this->payload())->assertOk();
        $this->assertDatabaseHas('customer', ['id' => 1, 'hrg_jual' => 140000000, 'total_harga' => 145000000, 'diskon' => 20000000, 'id_booking' => 1]);
        $this->assertDatabaseHas('piutang', ['id' => 1, 'nominal' => 140000000, 'terbayar' => 100000000, 'sisa_bayar' => 40000000]);
        $this->assertEquals(165000000, Piutang::sum('nominal'));
        $this->assertEquals(100000000, Pemasukan::sum('nominal'));
        $this->assertEquals(145000000, PengajuanHold::first()->total_harga);
        $this->assertEquals(140000000, PengajuanHold::first()->rincian_biaya[0]['nilai']);
        $this->assertEquals(150000000, \App\Models\KavlingPeta::first()->hrg_jual);
        $this->assertDatabaseHas('sppr', ['id_customer' => 1, 'harga_jual' => 140000000]);
    }

    public function test_lower_price_than_payments_preserves_overpayment_without_negative_balance(): void
    {
        app(CustomerSalePrice::class)->sync(Customer::first(), 90000000, 0);
        $this->assertEquals(100000000, Pemasukan::sum('nominal'));
        $this->assertDatabaseHas('piutang', ['id' => 1, 'nominal' => 90000000, 'terbayar' => 90000000, 'sisa_bayar' => 0, 'status' => 2]);
        $this->assertDatabaseHas('piutang', ['id' => 2, 'terbayar' => 5000000, 'sisa_bayar' => 0]);
        $this->assertDatabaseHas('piutang', ['id' => 3, 'terbayar' => 5000000, 'sisa_bayar' => 15000000]);
    }

    public function test_invalid_discount_and_ambiguous_bills_roll_back_the_entire_edit(): void
    {
        $this->putJson('/_test/customer-sale/1', array_merge($this->payload(), ['diskon' => '170.000.000']))->assertUnprocessable();
        Piutang::create(['id_customer' => 1, 'deskripsi' => 'Harga Rumah duplikat', 'nominal' => 1000]);
        $this->putJson('/_test/customer-sale/1', $this->payload())->assertUnprocessable();
        $this->assertDatabaseHas('customer', ['id' => 1, 'nama_lengkap' => 'CUSTOMER', 'hrg_jual' => 150000000]);
        $this->assertDatabaseHas('piutang', ['id' => 1, 'nominal' => 150000000]);
        $this->assertDatabaseHas('pengajuan_hold', ['id' => 1, 'total_harga' => 155000000]);
    }

    public function test_aggregate_house_bill_keeps_other_price_components(): void
    {
        DB::table('piutang')->where('id', 2)->delete();
        DB::table('piutang')->where('id', 1)->update(['nominal' => 155000000]);
        $this->putJson('/_test/customer-sale/1', $this->payload())->assertOk();
        $this->assertDatabaseHas('piutang', ['id' => 1, 'nominal' => 145000000, 'sisa_bayar' => 45000000]);
        $this->assertDatabaseHas('piutang', ['id' => 3, 'nominal' => 20000000]);
    }

    public function test_missing_receivables_are_created_using_new_agreed_price(): void
    {
        DB::table('piutang')->delete();
        $this->putJson('/_test/customer-sale/1', $this->payload())->assertOk();
        $this->assertDatabaseCount('piutang', 1);
        $this->assertDatabaseHas('piutang', ['id_customer' => 1, 'nominal' => 145000000, 'terbayar' => 100000000, 'sisa_bayar' => 45000000]);
    }

    public function test_print_document_context_uses_agreed_price_after_discount(): void
    {
        $this->putJson('/_test/customer-sale/1', $this->payload())->assertOk();
        $customer = Customer::first();
        $unit = \App\Models\KavlingPeta::first();
        $unit->setRelation('lokasi', null)->setRelation('perusahaan', null);
        $customer->setRelation('kavlingPeta', $unit)->setRelation('lokasiKavling', null);
        $context = \App\Services\DocumentDataContext::getAllForCustomer($customer);
        $this->assertSame('140.000.000', $context['harga_jual']);
        $this->assertSame('140.000.000', $context['hrg_jual']);
    }

    public function test_payment_bill_correction_keeps_sale_and_discount_in_sync(): void
    {
        $this->putJson('/_test/customer-sale/1', $this->payload())->assertOk();
        $this->putJson('/_test/customer-house-bill/1', ['nominal' => '130.000.000'])->assertOk();
        $this->assertDatabaseHas('customer', ['id' => 1, 'harga_jual_awal' => 150000000, 'diskon' => 20000000, 'hrg_jual' => 130000000, 'total_harga' => 135000000]);
        $this->assertDatabaseHas('piutang', ['id' => 1, 'nominal' => 130000000, 'sisa_bayar' => 30000000]);
        $this->assertDatabaseHas('sppr', ['id_customer' => 1, 'harga_jual' => 130000000]);
    }

    public function test_refresh_from_master_price_preserves_customer_discount(): void
    {
        $this->putJson('/_test/customer-sale/1', $this->payload())->assertOk();
        $this->putJson('/_test/customer-master-price/1')->assertOk();
        $this->assertDatabaseHas('customer', ['id' => 1, 'harga_jual_awal' => 150000000, 'diskon' => 20000000, 'hrg_jual' => 130000000]);
        $this->assertDatabaseHas('piutang', ['id' => 1, 'nominal' => 130000000]);
    }

    public function test_tempo_price_only_changes_financial_data_after_approval(): void
    {
        $this->actingAs((new User)->forceFill(['id' => 1, 'id_role' => 2, 'username' => 'tester']));
        $this->putJson('/_test/customer-sale/1', $this->payload())->assertOk()->assertJsonPath('tempo', true);
        $this->assertDatabaseHas('customer', ['id' => 1, 'hrg_jual' => 150000000]);
        $this->assertDatabaseHas('piutang', ['id' => 1, 'nominal' => 150000000]);
        $this->actingAs((new User)->forceFill(['id' => 2, 'id_role' => 1, 'username' => 'admin']));
        $this->postJson('/_test/customer-sale-approve/'.CustomerTempo::first()->id)->assertOk();
        $this->assertDatabaseHas('customer', ['id' => 1, 'hrg_jual' => 140000000, 'total_harga' => 145000000]);
        $this->assertDatabaseHas('piutang', ['id' => 1, 'nominal' => 140000000, 'sisa_bayar' => 40000000]);
        $this->assertDatabaseCount('customer_tempo', 0);
    }
}
