<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Services\CustomerKprDp;
use App\Services\CustomerReceivableService;
use App\Services\DashboardReceivableService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerKprDpTest extends CustomerReceivableTest
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::table('customer', function (Blueprint $t) { $t->string('jenis_pembelian')->nullable(); $t->integer('id_booking')->nullable(); });
        Schema::create('pengajuan_hold', fn (Blueprint $t) => $t->id());
        (require database_path('migrations/2026_11_14_030000_add_customer_dp_kpr.php'))->up();
        Schema::create('menu', function (Blueprint $t) { $t->id(); $t->string('route_name'); });
        Schema::create('hak_akses', function (Blueprint $t) {
            $t->id(); $t->integer('id_menu'); $t->integer('id_user'); $t->integer('lihat')->default(1); $t->integer('edit')->default(1);
        });
        DB::table('menu')->insert(['id' => 1, 'route_name' => 'customer.index']);
        DB::table('hak_akses')->insert(['id_user' => 1, 'id_menu' => 1]);
        $this->actingAs((new User)->forceFill(['id' => 1, 'id_role' => 1, 'username' => 'admin']));
    }

    private function kpr(): Customer
    {
        return Customer::create(['nama_lengkap' => 'Pembeli KPR', 'id_kavling' => 1, 'jenis_pembelian' => 'KPR',
            'total_harga' => 300000000, 'tgl_booking' => '2026-11-01', 'stt_arsip' => 0]);
    }

    public function test_legacy_house_bill_is_replaced_without_losing_booking_fee_or_linked_payments(): void
    {
        $customer = $this->kpr();
        DB::table('piutang')->insert(['id' => 1, 'id_customer' => $customer->id, 'id_bank' => 0, 'tanggal_piutang' => '2026-11-01',
            'deskripsi' => 'Harga Rumah Blok A1', 'nominal' => 300000000, 'lampiran' => '', 'status' => 1,
            'terbayar' => 0, 'sisa_bayar' => 300000000, 'id_kategori_transaksi' => 0]);
        DB::table('pemasukan')->insert([
            ['id_customer' => $customer->id, 'id_piutang' => 0, 'nominal' => 5000000, 'tanggal' => '2026-11-01', 'keterangan' => 'Booking Fee'],
            ['id_customer' => $customer->id, 'id_piutang' => 1, 'nominal' => 10000000, 'tanggal' => '2026-11-02', 'keterangan' => 'Angsuran DP'],
        ]);
        $this->getJson(route('customer.dp-kpr.show', $customer))->assertOk()->assertJsonPath('dp_kpr', null);
        $this->putJson(route('customer.dp-kpr.update', $customer), ['dp_kpr' => '30.000.000'])->assertOk();
        $this->assertDatabaseHas('customer', ['id' => $customer->id, 'dp_kpr' => 30000000, 'total_harga' => 300000000]);
        $this->assertDatabaseHas('piutang', ['id' => 1, 'nominal' => 30000000, 'terbayar' => 15000000, 'sisa_bayar' => 15000000, 'deskripsi' => 'DP KPR Blok A1']);
        $this->assertDatabaseCount('pemasukan', 2);
        $this->assertSame(['piutang' => 15000000, 'piutang_customer' => 1], app(DashboardReceivableService::class)->summary());
        $this->putJson(route('customer.dp-kpr.update', $customer), ['dp_kpr' => '30.000.000'])->assertOk();
        $this->assertDatabaseCount('piutang', 1);
    }

    public function test_unknown_dp_does_not_create_house_debt_and_zero_dp_keeps_payments(): void
    {
        $customer = $this->kpr();
        $this->assertFalse(app(CustomerReceivableService::class)->ensureForCustomer($customer->id));
        $this->assertDatabaseCount('piutang', 0);
        DB::table('pemasukan')->insert(['id_customer' => $customer->id, 'nominal' => 5000000, 'tanggal' => '2026-11-01', 'keterangan' => 'Booking Fee']);
        app(CustomerKprDp::class)->sync($customer->id, 0);
        $this->assertDatabaseHas('piutang', ['nominal' => 0, 'sisa_bayar' => 0, 'status' => 2]);
        $this->assertDatabaseHas('pemasukan', ['nominal' => 5000000]);
        app(CustomerKprDp::class)->sync($customer->id, 3000000);
        $this->assertDatabaseHas('piutang', ['nominal' => 3000000, 'terbayar' => 3000000, 'sisa_bayar' => 0]);
        $this->assertSame(['piutang' => 0, 'piutang_customer' => 0], app(DashboardReceivableService::class)->summary());
    }

    public function test_invalid_values_cash_customer_and_unpermitted_edits_are_rejected(): void
    {
        $customer = $this->kpr();
        foreach (['-1', '1e6', '301.000.000', ''] as $amount) {
            $this->putJson(route('customer.dp-kpr.update', $customer), ['dp_kpr' => $amount])->assertUnprocessable();
        }
        $customer->update(['jenis_pembelian' => 'Pembelian Cash']);
        $this->putJson(route('customer.dp-kpr.update', $customer), ['dp_kpr' => 1000000])->assertUnprocessable();
        $customer->update(['jenis_pembelian' => 'KPR']);
        DB::table('hak_akses')->update(['edit' => 0]);
        $this->putJson(route('customer.dp-kpr.update', $customer), ['dp_kpr' => 1000000])->assertForbidden();
        DB::table('hak_akses')->update(['edit' => 1]);
        $this->actingAs((new User)->forceFill(['id' => 1, 'id_role' => 2]));
        $this->putJson(route('customer.dp-kpr.update', $customer), ['dp_kpr' => 1000000])->assertForbidden();
        $this->assertDatabaseHas('customer', ['dp_kpr' => null]);
    }
}
