<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RekapTransaksiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('menu', function (Blueprint $t) {
            $t->id(); $t->integer('id_parent')->default(0); $t->string('title'); $t->string('route_name');
            $t->string('icon'); $t->integer('urutan');
            foreach (['lihat', 'tambah', 'edit', 'hapus'] as $field) $t->integer($field)->default(1);
        });
        foreach (['hak_akses' => 'id_user', 'role_user' => 'id_role'] as $table => $owner) {
            Schema::create($table, function (Blueprint $t) use ($owner) {
                $t->id(); $t->integer($owner); $t->integer('id_menu'); $t->integer('beranda')->default(0);
                foreach (['lihat', 'tambah', 'edit', 'hapus'] as $field) $t->integer($field)->default(1);
            });
        }
        Schema::create('konfigurasi', fn (Blueprint $t) => $t->id());
        Schema::create('konfigurasi_media', function (Blueprint $t) { $t->id(); $t->string('jenis_data'); $t->integer('stt_aktif'); });
        DB::table('menu')->insert([
            ['id' => 1, 'title' => 'SPK', 'route_name' => 'spk.index', 'icon' => 'fas fa-file', 'urutan' => 10],
            ['id' => 2, 'title' => 'Setelah SPK', 'route_name' => 'tukang.index', 'icon' => 'fas fa-users', 'urutan' => 11],
        ]);
        DB::table('hak_akses')->insert(['id_user' => 1, 'id_menu' => 1]);
        DB::table('role_user')->insert(['id_role' => 1, 'id_menu' => 1]);
        (require database_path('migrations/2026_11_20_020000_add_rekap_transaksi_menu.php'))->up();
        Schema::create('kategori_transaksi', function (Blueprint $t) { $t->id(); $t->string('kategori'); });
        foreach (['pemasukan' => 'no_kwitansi', 'pengeluaran' => 'no_tanda_terima'] as $table => $reference) {
            Schema::create($table, function (Blueprint $t) use ($reference) {
                $t->id(); $t->dateTime('tanggal'); $t->integer('id_kategori_transaksi')->nullable();
                $t->unsignedBigInteger('nominal'); $t->string($reference)->nullable(); $t->text('keterangan')->nullable();
            });
        }
        Schema::create('barang_masuk_pembayaran', function (Blueprint $t) {
            $t->id(); $t->integer('id_barang_masuk'); $t->dateTime('tanggal_pembayaran'); $t->unsignedBigInteger('jumlah_bayar'); $t->text('keterangan')->nullable();
        });
        Schema::create('spk', function (Blueprint $t) { $t->id(); $t->string('nomor'); });
        Schema::create('spk_pembayaran', function (Blueprint $t) {
            $t->id(); $t->integer('id_spk'); $t->dateTime('tanggal'); $t->unsignedBigInteger('nominal'); $t->text('keterangan')->nullable();
        });
        $user = new User(); $user->id = 1; $user->username = 'test'; $this->actingAs($user);
    }

    private function filters(array $changes = []): array
    {
        return array_merge(['tanggal_awal' => '2026-11-01', 'tanggal_akhir' => '2026-11-20', 'tampilan' => 'ringkasan', 'preview' => 1], $changes);
    }

    private function transactions(): void
    {
        DB::table('kategori_transaksi')->insert(['id' => 1, 'kategori' => 'Operasional']);
        foreach ([['2026-11-01 00:00:00', 100], ['2026-11-20 23:59:59', 200], ['2026-10-31 23:59:59', 999], ['2026-11-21 00:00:00', 999]] as [$date, $amount]) {
            DB::table('pemasukan')->insert(['tanggal' => $date, 'id_kategori_transaksi' => 1, 'nominal' => $amount]);
        }
        DB::table('pemasukan')->insert(['tanggal' => '2026-11-10', 'id_kategori_transaksi' => 999, 'nominal' => 10]);
        DB::table('pengeluaran')->insert(['tanggal' => '2026-11-05', 'id_kategori_transaksi' => 1, 'nominal' => 50, 'keterangan' => '<script>alert(1)</script>']);
        DB::table('barang_masuk_pembayaran')->insert(['tanggal_pembayaran' => '2026-11-20 23:59:59', 'id_barang_masuk' => 12, 'jumlah_bayar' => 40]);
        DB::table('spk')->insert(['id' => 1, 'nomor' => 'SPK-TEST']);
        DB::table('spk_pembayaran')->insert(['id_spk' => 1, 'tanggal' => '2026-11-01', 'nominal' => 60]);
    }

    public function test_menu_is_below_spk_and_migration_preserves_permissions_when_repeated(): void
    {
        $menu = DB::table('menu')->where('route_name', 'rekap-transaksi.index')->first();
        $this->assertSame(11, $menu->urutan);
        $this->assertDatabaseHas('menu', ['id' => 2, 'urutan' => 12]);
        $this->assertDatabaseHas('role_user', ['id_menu' => $menu->id, 'id_role' => 1, 'lihat' => 1, 'edit' => 0]);
        DB::table('hak_akses')->where('id_menu', $menu->id)->update(['lihat' => 0]);
        (require database_path('migrations/2026_11_20_020000_add_rekap_transaksi_menu.php'))->up();
        $this->assertSame(1, DB::table('menu')->where('route_name', 'rekap-transaksi.index')->count());
        $this->assertDatabaseHas('menu', ['id' => 2, 'urutan' => 12]);
        $this->get(route('rekap-transaksi.index'))->assertForbidden();
        $this->get(route('rekap-transaksi.index', $this->filters(['format' => 'pdf'])))->assertForbidden();
    }

    public function test_form_and_empty_preview_render_and_invalid_periods_are_rejected(): void
    {
        $this->get(route('rekap-transaksi.index'))->assertOk()->assertSee('Tanggal Awal')->assertDontSee('Cetak PDF');
        $this->get(route('rekap-transaksi.index', $this->filters()))->assertOk()->assertSee('Tidak ada transaksi pada periode ini.');
        $this->getJson(route('rekap-transaksi.index', $this->filters(['tanggal_akhir' => '2026-10-31'])))->assertUnprocessable()->assertJsonValidationErrors('tanggal_akhir');
        $this->getJson(route('rekap-transaksi.index', ['format' => 'pdf']))->assertUnprocessable()->assertJsonValidationErrors(['tanggal_awal', 'tanggal_akhir']);
        $this->getJson(route('rekap-transaksi.index', $this->filters(['tampilan' => 'lainnya'])))->assertUnprocessable();
    }

    public function test_summary_and_detail_only_include_income_and_expenses_and_both_boundary_days(): void
    {
        $this->transactions();
        $this->get(route('rekap-transaksi.index', $this->filters()))->assertOk()
            ->assertDontSee('Pembayaran Barang Masuk')->assertDontSee('Pembayaran SPK')
            ->assertViewHas('report', fn ($r) => $r['income'] == 310 && $r['expense'] == 50 && $r['rows']->count() === 2
                && $r['rows']->firstWhere('kategori', 'Operasional')->pemasukan == 300
                && $r['rows']->firstWhere('kategori', 'Operasional')->pengeluaran == 50);
        $this->get(route('rekap-transaksi.index', $this->filters(['tampilan' => 'detail'])))->assertOk()
            ->assertDontSee('BM-00012')->assertDontSee('SPK-TEST')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)
            ->assertViewHas('report', fn ($r) => $r['income'] == 310 && $r['expense'] == 50 && $r['rows']->count() === 4
                && $r['rows']->every(fn ($row) => in_array($row->sumber, ['Pemasukan', 'Pengeluaran'], true)));
    }

    public function test_both_modes_generate_inline_pdf(): void
    {
        $this->transactions();
        foreach (['ringkasan', 'detail'] as $mode) {
            $response = $this->get(route('rekap-transaksi.index', $this->filters(['tampilan' => $mode, 'format' => 'pdf'])))
                ->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
            $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
        }
    }
}
