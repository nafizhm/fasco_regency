<?php

namespace Tests\Feature;

use App\Models\Spk;
use App\Models\User;
use App\Services\SpkWordService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class SpkTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('menu', function (Blueprint $t) {
            $t->id(); $t->integer('id_parent')->default(0); $t->string('title'); $t->string('route_name');
            $t->string('icon'); $t->integer('urutan')->default(0);
            foreach (['lihat', 'tambah', 'edit', 'hapus'] as $key) $t->integer($key)->default(1);
        });
        Schema::create('hak_akses', function (Blueprint $t) {
            $t->id(); $t->integer('id_user'); $t->integer('id_menu'); $t->integer('beranda')->default(0);
            foreach (['lihat', 'tambah', 'edit', 'hapus'] as $key) $t->integer($key)->default(1);
        });
        Schema::create('konfigurasi', fn (Blueprint $t) => $t->id());
        Schema::create('konfigurasi_media', function (Blueprint $t) {
            $t->id(); $t->string('jenis_data'); $t->integer('stt_aktif')->default(1);
        });
        DB::table('menu')->insert(['id' => 1, 'title' => 'Hak Akses', 'route_name' => 'hak-akses.index', 'icon' => 'fas fa-lock']);
        DB::table('hak_akses')->insert(['id_user' => 1, 'id_menu' => 1]);
        (require database_path('migrations/2026_11_14_020000_create_spk.php'))->up();
        $user = new User(); $user->id = 1; $user->username = 'test'; $this->actingAs($user);
    }

    private function payload(): array
    {
        return [
            'nomor' => '001-N/SPK-B2/FR/XI/2026', 'tanggal' => '2026-11-14',
            'nama_perusahaan' => 'PT. DWI RAKA & GRAHA', 'alamat_perusahaan' => 'Alamat perusahaan',
            'telp_perusahaan' => '0813-3535-2277', 'penandatangan' => 'Laji Siswanto', 'kota' => 'Malang',
            'nama_pemborong' => 'Budi & Rekan', 'jabatan_pemborong' => 'Pemborong', 'alamat_pemborong' => 'Alamat pemborong',
            'nama_proyek' => 'FASCO REGENCY', 'lokasi_proyek' => 'Boro Bugis', 'jenis_pekerjaan' => 'Pasang dinding <B2>',
            'tanggal_mulai' => '2026-11-15', 'tanggal_selesai' => '2026-12-15', 'lama_pengerjaan' => '30 hari kerja',
            'skema_pembayaran' => 'DP 50%, sisanya setelah selesai',
            'items' => [
                ['item' => 'Dinding & plester', 'volume' => '2.5', 'satuan' => 'm2', 'harga_satuan' => 100000],
                ['item' => 'Cat <B2>', 'volume' => '3', 'satuan' => 'm2', 'harga_satuan' => 50000],
            ],
        ];
    }

    private function createSpk(): Spk
    {
        $this->post(route('spk.store'), $this->payload())->assertRedirect();
        return Spk::latest('id')->firstOrFail();
    }

    private function payment(Spk $spk, int $amount, ?string $token = null)
    {
        return $this->postJson(route('spk.payment', $spk), ['tanggal' => '2026-11-14', 'nominal' => $amount,
            'metode' => 'Transfer', 'referensi' => 'REF-1', 'token' => $token ?? (string) Str::uuid()]);
    }

    public function test_create_edit_and_views_use_server_calculated_totals(): void
    {
        $this->get(route('spk.create'))->assertOk()->assertSee('Rincian Borongan');
        $spk = $this->createSpk();
        $this->assertSame(400000, $spk->total_rincian);
        $this->assertSame(400000, $spk->nilai_borongan);
        $this->assertSame('Belum Dibayar', $spk->status_pembayaran);
        $this->get(route('spk.index'))->assertOk()->assertSee('Belum Dibayar')->assertSee('400.000');
        $this->get(route('spk.show', $spk))->assertOk()->assertSee('Budi &amp; Rekan', false)->assertSee('Cetak Word');
        $this->get(route('spk.edit', $spk))->assertOk();
        $data = $this->payload(); $data['versi'] = 0; $data['nilai_borongan'] = 390000; $data['total_rincian'] = 1; $data['items'][0]['total'] = 1;
        $this->put(route('spk.update', $spk), $data)->assertRedirect();
        $this->assertDatabaseHas('spk', ['id' => $spk->id, 'total_rincian' => 400000, 'nilai_borongan' => 390000, 'versi' => 1]);
        $this->putJson(route('spk.update', $spk), $data)->assertUnprocessable()->assertJsonValidationErrors('versi');
    }

    public function test_partial_full_and_duplicate_payments_and_filters(): void
    {
        $spk = $this->createSpk(); $token = (string) Str::uuid();
        $this->payment($spk, 150000, $token)->assertRedirect();
        $this->payment($spk, 150000, $token)->assertRedirect();
        $this->assertDatabaseCount('spk_pembayaran', 1);
        $this->assertSame('Terbayar Sebagian', $spk->fresh()->status_pembayaran);
        $this->get(route('spk.index', ['status' => 'sebagian']))->assertOk()->assertSee($spk->nomor);
        $this->get(route('spk.index', ['status' => 'belum']))->assertOk()->assertDontSee($spk->nomor);
        $this->payment($spk, 250001)->assertUnprocessable()->assertJsonValidationErrors('nominal');
        $this->payment($spk, 250000)->assertRedirect();
        $this->assertSame('Lunas', $spk->fresh()->status_pembayaran);
        $this->assertSame(0, $spk->fresh()->sisa);
        $this->get(route('spk.index', ['status' => 'lunas']))->assertOk()->assertSee($spk->nomor);
        $this->get(route('spk.show', $spk))->assertOk()->assertDontSee('id="payment-form"', false);
        $this->payment($spk, 1)->assertUnprocessable();
    }

    public function test_paid_spk_is_protected_and_invalid_input_is_rejected(): void
    {
        $spk = $this->createSpk();
        $this->payment($spk, 150000)->assertRedirect();
        $this->deleteJson(route('spk.destroy', $spk))->assertUnprocessable();
        $data = $this->payload(); $data['versi'] = 0; $data['nilai_borongan'] = 100000;
        $this->putJson(route('spk.update', $spk), $data)->assertUnprocessable()->assertJsonValidationErrors('nilai_borongan');
        $data['nilai_borongan'] = 400000; $data['tanggal'] = '2026-11-15';
        $this->putJson(route('spk.update', $spk), $data)->assertUnprocessable()->assertJsonValidationErrors('tanggal');
        $this->postJson(route('spk.store'), $this->payload())->assertUnprocessable()->assertJsonValidationErrors('nomor');
        $data = $this->payload(); $data['nomor'] = 'SPK-2'; $data['items'][0]['volume'] = '-1';
        $this->postJson(route('spk.store'), $data)->assertUnprocessable();
        $data['items'][0]['volume'] = '1'; $data['tanggal_selesai'] = '2026-11-01';
        $this->postJson(route('spk.store'), $data)->assertUnprocessable();
        $this->postJson(route('spk.payment', $spk), ['tanggal' => '2026-11-01', 'nominal' => 1000, 'token' => (string) Str::uuid()])->assertUnprocessable()->assertJsonValidationErrors('tanggal');
        $this->assertDatabaseCount('spk', 1);
    }

    public function test_permissions_are_enforced_and_unpaid_spk_can_be_deleted(): void
    {
        $spk = $this->createSpk();
        $id = DB::table('menu')->where('route_name', 'spk.index')->value('id');
        DB::table('hak_akses')->where('id_menu', $id)->update(['tambah' => 0, 'edit' => 0, 'hapus' => 0]);
        $this->postJson(route('spk.store'), $this->payload())->assertForbidden();
        $this->get(route('spk.edit', $spk))->assertForbidden();
        $this->payment($spk, 1000)->assertForbidden();
        $this->deleteJson(route('spk.destroy', $spk))->assertForbidden();
        DB::table('hak_akses')->where('id_menu', $id)->update(['lihat' => 0]);
        $this->get(route('spk.word', $spk))->assertForbidden();
        $this->get(route('spk.index'))->assertForbidden();
        DB::table('hak_akses')->where('id_menu', $id)->update(['lihat' => 1, 'hapus' => 1]);
        $this->delete(route('spk.destroy', $spk))->assertRedirect(route('spk.index'));
        $this->assertDatabaseCount('spk', 0);
    }

    public function test_word_preserves_template_header_and_expands_items_with_xml_escaping(): void
    {
        $spk = $this->createSpk();
        $items = $spk->items;
        $items[] = ['item' => 'Pekerjaan ketiga', 'volume' => 1, 'satuan' => 'ls', 'harga_satuan' => 10000, 'total' => 10000];
        $items[] = ['item' => 'Pekerjaan keempat', 'volume' => 1, 'satuan' => 'ls', 'harga_satuan' => 20000, 'total' => 20000];
        $spk->update(['items' => $items, 'nilai_borongan' => 420000, 'total_rincian' => 430000]);
        $path = app(SpkWordService::class)->generate($spk);
        try {
            $zip = new \ZipArchive(); $this->assertTrue($zip->open($path));
            foreach (['word/document.xml', 'word/header1.xml'] as $part) {
                $xml = $zip->getFromName($part);
                $doc = new \DOMDocument(); $this->assertTrue($doc->loadXML($xml));
                $this->assertStringNotContainsString('${', $xml);
                $this->assertStringContainsString('PT. DWI RAKA &amp; GRAHA', $xml);
                if ($part === 'word/document.xml') {
                    $this->assertStringContainsString('Pekerjaan keempat', $xml);
                    $this->assertStringContainsString('Budi &amp; Rekan', $xml);
                    $this->assertStringContainsString('420.000', $xml);
                    $this->assertStringContainsString('430.000', $xml);
                    $this->assertStringContainsString('Cat &lt;B2&gt;', $xml);
                }
            }
            $this->assertNotFalse($zip->getFromName('word/styles.xml'));
            $zip->close();
        } finally { @unlink($path); }
        $response = $this->get(route('spk.word', $spk))->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertStringContainsString('.docx', $response->headers->get('content-disposition'));
        @unlink($response->baseResponse->getFile()->getPathname());
    }
}
