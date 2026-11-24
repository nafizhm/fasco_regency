<?php

namespace Tests\Feature;

use App\Services\DashboardReceivableService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardReceivableTest extends TestCase
{
    public function test_summary_uses_actual_unpaid_balances_of_active_customers(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('customer', function (Blueprint $table) {
            $table->id();
            $table->integer('stt_arsip')->default(0);
            $table->date('tanggal_jatuh_tempo')->nullable();
        });
        Schema::create('piutang', function (Blueprint $table) {
            $table->string('jenis_piutang')->nullable();
            $table->id();
            $table->integer('id_customer');
            $table->integer('nominal');
        });
        Schema::create('kategori_transaksi', function (Blueprint $table) { $table->id(); $table->string('kategori'); });
        DB::table('kategori_transaksi')->insert(['id' => 9, 'kategori' => 'Pencairan KPR']);
        Schema::create('pemasukan', function (Blueprint $table) {
            $table->integer('id_kategori_transaksi')->default(1);
            $table->id();
            $table->integer('id_customer');
            $table->integer('nominal');
            $table->string('keterangan')->nullable();
        });
        DB::table('customer')->insert([
            ['id' => 1, 'stt_arsip' => 0], ['id' => 2, 'stt_arsip' => 0],
            ['id' => 3, 'stt_arsip' => 1], ['id' => 4, 'stt_arsip' => 0],
            ['id' => 5, 'stt_arsip' => 0],
        ]);
        foreach ([[1, 1000], [1, 500], [2, 100], [3, 9000], [4, 200]] as [$id, $amount]) {
            DB::table('piutang')->insert(['id_customer' => $id, 'nominal' => $amount]);
        }
        foreach ([[1, 300, null], [1, 200, 'DP'], [1, 800, 'Biaya ganti nama customer'],
            [2, 400, 'Lunas']] as [$id, $amount, $description]) {
            DB::table('pemasukan')->insert(['id_customer' => $id, 'nominal' => $amount, 'keterangan' => $description]);
        }

        DB::table('piutang')->insert(['id_customer' => 1, 'nominal' => 6000, 'jenis_piutang' => 'bank']);
        DB::table('pemasukan')->insert(['id_customer' => 1, 'nominal' => 5500, 'id_kategori_transaksi' => 9]);
        $today = now('Asia/Jakarta')->toDateString();
        DB::table('customer')->whereIn('id', [1, 2, 5])->update(['tanggal_jatuh_tempo' => $today]);
        DB::table('customer')->whereIn('id', [3, 4])->update(['tanggal_jatuh_tempo' => now('Asia/Jakarta')->subDay()->toDateString()]);
        $summary = app(DashboardReceivableService::class)->summary();
        $this->assertSame(['piutang' => 1200, 'piutang_customer' => 2,
            'jatuh_tempo_hari_ini' => 1, 'terlambat' => 1, 'notifikasi_piutang' => 2], $summary);
        $this->view('admin.dashboard.receivable-bell', ['receivables' => $summary])
            ->assertSee(route('dashboard.piutang', ['jatuh_tempo' => 1]))->assertSee('2 notifikasi piutang')->assertSee('fa-bell');
        $due = app(DashboardReceivableService::class)->customers([], true);
        $this->assertSame([1, 4], $due->pluck('id')->all());
        $this->assertSame(1200, $due->sum('sisa_piutang'));
        DB::table('customer')->where('id', 1)->update(['tanggal_jatuh_tempo' => now('Asia/Jakarta')->addDay()->toDateString()]);
        $due = app(DashboardReceivableService::class)->customers([], true);
        $this->assertSame([4], $due->pluck('id')->all());
        $this->assertSame(200, $due->sum('sisa_piutang'));
        DB::table('customer')->where('id', 4)->update(['tanggal_jatuh_tempo' => null]);
        $this->assertSame(0, app(DashboardReceivableService::class)->summary()['notifikasi_piutang']);
        $due = app(DashboardReceivableService::class)->customers([], true);
        $this->assertCount(0, $due);
        $this->assertEquals(0, $due->sum('sisa_piutang'));
        DB::table('customer')->where('id', 1)->update(['tanggal_jatuh_tempo' => $today]);
        DB::table('customer')->where('id', 4)->update(['tanggal_jatuh_tempo' => now('Asia/Jakarta')->subDay()->toDateString()]);
        $customers = app(DashboardReceivableService::class)->customers();
        $this->assertSame([1, 4], $customers->pluck('id')->all());
        $this->assertSame([1000, 200], $customers->pluck('sisa_piutang')->all());
        $this->assertSame(1500, (int) $customers->first()->total_tagihan);
        $this->assertSame(500, (int) $customers->first()->total_bayar);
        DB::table('pemasukan')->insert(['id_customer' => 1, 'nominal' => 1000, 'keterangan' => 'Pelunasan']);
        $this->assertSame(['piutang' => 200, 'piutang_customer' => 1,
            'jatuh_tempo_hari_ini' => 0, 'terlambat' => 1, 'notifikasi_piutang' => 1], app(DashboardReceivableService::class)->summary());
        $this->assertSame([4], app(DashboardReceivableService::class)->customers()->pluck('id')->all());

        Schema::table('customer', function (Blueprint $table) {
            $table->string('nama_lengkap')->nullable();
            $table->integer('id_lokasi')->nullable();
            $table->integer('id_kavling')->nullable();
        });
        Schema::create('kavling_peta', function (Blueprint $table) {
            $table->id(); $table->string('nama_cluster'); $table->string('kode_kavling');
        });
        DB::table('kavling_peta')->insert([
            ['id' => 17, 'nama_cluster' => 'KUNING', 'kode_kavling' => '17'],
            ['id' => 18, 'nama_cluster' => 'KUNING', 'kode_kavling' => '18'],
        ]);
        DB::table('customer')->insert([
            ['id' => 8, 'nama_lengkap' => 'SHOFYAN SANTRIANSYAH', 'id_lokasi' => 1, 'id_kavling' => 17, 'tanggal_jatuh_tempo' => $today],
            ['id' => 9, 'nama_lengkap' => 'SHOFYAN', 'id_lokasi' => 1, 'id_kavling' => 18, 'tanggal_jatuh_tempo' => now('Asia/Jakarta')->addDay()->toDateString()],
        ]);
        DB::table('piutang')->insert([
            ['id_customer' => 8, 'nominal' => 1000], ['id_customer' => 9, 'nominal' => 2000],
        ]);
        DB::table('pemasukan')->insert(['id_customer' => 9, 'nominal' => 500]);
        $grouped = app(DashboardReceivableService::class)->customers(['kavling', 'piutangs', 'pemasukans']);
        $this->assertSame([8, 4], $grouped->pluck('id')->all());
        $shofyan = $grouped->first();
        $this->assertSame(3000, $shofyan->total_tagihan);
        $this->assertSame(500, $shofyan->total_bayar);
        $this->assertSame(2500, $shofyan->sisa_piutang);
        $this->assertCount(2, $shofyan->piutangs);
        $this->assertCount(1, $shofyan->pemasukans);
        $this->assertSame('KUNING / Blok 17 & KUNING / Blok 18', $shofyan->gabungan_unit);
        $this->assertSame(2500, app(DashboardReceivableService::class)->customers([], true)->first()->sisa_piutang);
    }
}
