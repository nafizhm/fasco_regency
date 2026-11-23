<?php

namespace Tests\Feature;

use App\Models\{FeeMarketing, User};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class FeeMarketingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('menu', function (Blueprint $t) {
            $t->id(); $t->integer('id_parent')->default(0); $t->string('title'); $t->string('route_name');
            $t->string('icon'); $t->integer('urutan');
            foreach (['lihat','tambah','edit','hapus'] as $f) $t->integer($f)->default(1);
        });
        foreach (['hak_akses'=>'id_user','role_user'=>'id_role'] as $table=>$owner) {
            Schema::create($table, function (Blueprint $t) use ($owner) {
                $t->id(); $t->integer($owner); $t->integer('id_menu'); $t->integer('beranda')->default(0);
                foreach (['lihat','tambah','edit','hapus'] as $f) $t->integer($f)->default(1);
            });
        }
        Schema::create('konfigurasi', fn (Blueprint $t) => $t->id());
        Schema::create('konfigurasi_media', function (Blueprint $t) { $t->id(); $t->string('jenis_data'); $t->integer('stt_aktif'); });
        DB::table('menu')->insert(['id'=>1,'title'=>'Pemasukan','route_name'=>'pemasukan.index','icon'=>'fas fa-money-bill','urutan'=>1]);
        DB::table('hak_akses')->insert(['id_user'=>1,'id_menu'=>1]);
        DB::table('role_user')->insert(['id_role'=>1,'id_menu'=>1]);
        (require database_path('migrations/2026_11_23_000000_create_fee_marketing.php'))->up();
        $user = new User; $user->id = 1; $user->username = 'test'; $this->actingAs($user);
    }

    private function payload(array $changes = []): array
    {
        return array_replace([
            'nomor'=>'001-P/I-24/FR/X/2026','tanggal'=>'2026-11-23',
            'perusahaan'=>'PT. Dwi Raka Graha','subjudul'=>'DEVELOPER DAN CONTRACTOR',
            'alamat'=>'Tegal Bugis, Kelurahan Saptorenggo, Kecamatan Pakis, Kabupaten Malang',
            'telepon'=>'0813-3535-2277','diterima_dari'=>'PT. Dwi Raka Graha',
            'leader_tim'=>'Andrian Pujiantoro / Tim Pratama','untuk_pembayaran'=>'fee pelunasan 1 unit Rumah Fasco Regency',
            'blok_skema'=>'Jingga 24 / cash','nama_user'=>'Precillia','kota'=>'Malang',
            'proyek'=>'Fasco Regency','owner'=>'Laji Siswanto','jabatan_penerima'=>'Leader',
            'bank_rekening'=>'BCA 123456','atas_nama'=>'Andrian',
            'catatan_harga'=>'*harga deal tidak termasuk Biaya Admin / tambahan lain-lain',
            'ketentuan'=>"1. Fee diserahkan setelah tandatangan bukti pembayaran tanda terima fee marketing\n2. Fee diserahkan Secara Cash,Cek Atau Transfer ke :",
            'rincian'=>[
                ['blok'=>'Jingga 24','level'=>'Marketing','harga_deal'=>'816500000','blt'=>'0','persen'=>'2.5','fee_sebelumnya'=>'0','fee_saat_ini'=>'20412500'],
                ['blok'=>'Jingga 24','level'=>'Leader','harga_deal'=>'816500000','blt'=>'0','persen'=>'1.5','fee_sebelumnya'=>'0','fee_saat_ini'=>'12247500'],
            ],
            'riwayat'=>[['tanggal'=>'2026-01-09','keterangan'=>'fee blt','nominal'=>'1000000']],
        ], $changes);
    }

    public function test_crud_dynamic_rows_totals_and_print(): void
    {
        $this->get(route('fee-marketing.index'))->assertOk()->assertSee('Belum ada Fee Marketing.');
        $this->get(route('fee-marketing.create'))->assertOk()->assertSee('Tambah Rincian')->assertSee('inputmode="numeric"', false);
        $this->post(route('fee-marketing.store'), $this->payload())->assertRedirect(route('fee-marketing.show', 1));
        $record = FeeMarketing::findOrFail(1);
        $this->assertSame(32660000, $record->jumlah);
        $this->assertSame(20412500, $record->data['rincian'][0]['komisi']);
        $this->get(route('fee-marketing.show', 1))->assertOk()->assertSee('32.660.000');
        $this->get(route('fee-marketing.edit', 1))->assertOk()->assertSee('816500000');
        $pdf = $this->get(route('fee-marketing.pdf', 1))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        // Keep a review artifact only when explicitly requested by the local test run.
        if (getenv('FEE_PDF_PREVIEW')) file_put_contents(storage_path('app/fee-marketing-preview.pdf'), $pdf->getContent());
        $input = $this->payload(['riwayat'=>[], 'nomor'=>'FM-UPDATED']);
        $input['rincian'] = [$input['rincian'][1]];
        $this->put(route('fee-marketing.update',1), $input)->assertRedirect();
        $this->assertSame(12247500, $record->fresh()->jumlah);
        $this->assertCount(1, $record->fresh()->data['rincian']);
        $this->assertSame([], $record->fresh()->data['riwayat']);
        $this->get(route('fee-marketing.index', ['q'=>'FM-UPDATED']))->assertOk()->assertSee('FM-UPDATED');
        $this->delete(route('fee-marketing.destroy',1))->assertRedirect(route('fee-marketing.index'));
        $this->assertDatabaseCount('fee_marketing',0);
    }

    public function test_invalid_money_percent_and_duplicate_number_are_rejected(): void
    {
        foreach (['-1','1.5','2.500.000','abc','1000000000001'] as $invalid) {
            $input = $this->payload(); $input['rincian'][0]['harga_deal'] = $invalid;
            $this->postJson(route('fee-marketing.store'),$input)->assertUnprocessable()->assertJsonValidationErrors('rincian.0.harga_deal');
        }
        $input = $this->payload(); $input['rincian'][0]['persen'] = 101;
        $this->postJson(route('fee-marketing.store'),$input)->assertUnprocessable()->assertJsonValidationErrors('rincian.0.persen');
        $this->post(route('fee-marketing.store'),$this->payload())->assertRedirect();
        $this->postJson(route('fee-marketing.store'),$this->payload())->assertUnprocessable()->assertJsonValidationErrors('nomor');
        $this->assertSame('Tiga Puluh Dua Juta Enam Ratus Enam Puluh Ribu Rupiah', \App\Services\FeeMarketingWords::rupiah(32660000));
        $this->assertSame('Nol Rupiah', \App\Services\FeeMarketingWords::rupiah(0));
    }

    public function test_permissions_are_enforced_and_migration_is_repeatable(): void
    {
        $this->post(route('fee-marketing.store'),$this->payload())->assertRedirect();
        $id = DB::table('menu')->where('route_name','fee-marketing.index')->value('id');
        DB::table('hak_akses')->where('id_menu',$id)->update(['tambah'=>0,'edit'=>0,'hapus'=>0]);
        (require database_path('migrations/2026_11_23_000000_create_fee_marketing.php'))->up();
        $this->assertSame(1,DB::table('menu')->where('route_name','fee-marketing.index')->count());
        $this->get(route('fee-marketing.create'))->assertForbidden();
        $this->post(route('fee-marketing.store'),$this->payload())->assertForbidden();
        $this->get(route('fee-marketing.edit',1))->assertForbidden();
        $this->put(route('fee-marketing.update',1),$this->payload())->assertForbidden();
        $this->delete(route('fee-marketing.destroy',1))->assertForbidden();
        $this->get(route('fee-marketing.pdf',1))->assertOk();
        DB::table('hak_akses')->where('id_menu',$id)->update(['lihat'=>0]);
        $this->get(route('fee-marketing.index'))->assertForbidden();
        $this->get(route('fee-marketing.pdf',1))->assertForbidden();
        $this->get(route('fee-marketing.show',1))->assertForbidden();
    }

    public function test_many_fee_rows_print_across_pages(): void
    {
        $input = $this->payload();
        $row = $input['rincian'][0];
        $input['rincian'] = [];
        for ($i = 1; $i <= 40; $i++) {
            $input['rincian'][] = array_replace($row, ['blok' => 'Unit '.$i, 'level' => 'Marketing '.$i]);
        }
        $this->post(route('fee-marketing.store'), $input)->assertRedirect();
        $record = FeeMarketing::findOrFail(1);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.fee_marketing.pdf', [
            'record' => $record, 'd' => $record->data,
            'terbilang' => \App\Services\FeeMarketingWords::rupiah($record->jumlah),
        ])->setPaper('a4', 'portrait');
        $pdf->output();
        $this->assertGreaterThan(1, $pdf->getDomPDF()->getCanvas()->get_page_count());
        $this->assertSame(40 * 20412500, $record->jumlah);
    }
}

