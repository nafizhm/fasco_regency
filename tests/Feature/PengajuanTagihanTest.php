<?php

namespace Tests\Feature;

use App\Models\PengajuanTagihan;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PengajuanTagihanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('username');
        });
        DB::table('users')->insert([['id' => 1, 'username' => 'nita'], ['id' => 2, 'username' => 'other']]);
        Schema::create('menu', function (Blueprint $t) {
            $t->id();
            $t->integer('id_parent');
            $t->string('title');
            $t->string('route_name');
            $t->string('icon');
            $t->integer('urutan');
            foreach (['lihat', 'tambah', 'edit', 'hapus'] as $key) {
                $t->integer($key);
            }
        });
        foreach (['hak_akses' => 'id_user', 'role_user' => 'id_role'] as $table => $owner) {
            Schema::create($table, function (Blueprint $t) use ($owner) {
                $t->id();
                $t->integer($owner);
                $t->integer('id_menu');
                $t->integer('beranda')->default(0);
                foreach (['lihat', 'tambah', 'edit', 'hapus'] as $key) {
                    $t->integer($key);
                }
            });
        }
        Schema::create('konfigurasi', fn (Blueprint $t) => $t->id());
        Schema::create('konfigurasi_media', function (Blueprint $t) {
            $t->id();
            $t->string('jenis_data');
            $t->integer('stt_aktif');
        });
        (require database_path('migrations/2026_11_28_000000_create_pengajuan_tagihan.php'))->up();
        $user = new User;
        $user->id = 1;
        $user->username = 'nita';
        $this->actingAs($user);
        Storage::fake('local');
    }

    private function payload(): array
    {
        return ['nomor' => 'PT-TEST', 'tanggal' => '2026-11-28', 'status' => 'Diajukan', 'detail' => [
            ['nama' => 'Barokah Jaya', 'uraian' => 'Pipa, besi, lem, kabel', 'tanggal_nota_max' => '2026-11-20',
                'fasco_regency' => '12224000', 'tegalgondo' => '0', 'ruko' => '9105000', 'bedali' => '0', 'lumajang' => '0', 'rekening' => 'BCA 123456'],
        ]];
    }

    public function test_crud_totals_and_attachment_lifecycle(): void
    {
        $this->get(route('pengajuan-tagihan.index'))->assertOk();
        $this->get(route('pengajuan-tagihan.create'))->assertOk()->assertSee('inputmode="numeric"', false);
        $input = $this->payload();
        $input['detail'][0]['file'] = UploadedFile::fake()->create('nota.pdf', 20, 'application/pdf');
        $this->post(route('pengajuan-tagihan.store'), $input)->assertRedirect(route('pengajuan-tagihan.index'));
        $data = PengajuanTagihan::with('detail')->firstOrFail();
        $line = $data->detail[0];
        $this->assertSame(21329000, $data->detail->sum('total'));
        Storage::disk('local')->assertExists($line->lampiran);
        $this->get(route('pengajuan-tagihan.attachment', [1, $line->id]))->assertOk()->assertDownload('nota.pdf');
        $this->get(route('pengajuan-tagihan.show', 1))->assertOk()->assertSee('21.329.000');
        $this->get(route('pengajuan-tagihan.edit', 1))->assertOk()->assertSee('nota.pdf');
        $input = $this->payload();
        $input['status'] = 'Disetujui';
        $input['detail'] = [$input['detail'][0]];
        $input['detail'][0]['id'] = $line->id;
        $this->put(route('pengajuan-tagihan.update', 1), $input)->assertRedirect();
        $this->assertSame($line->lampiran, $line->fresh()->lampiran);
        $this->assertDatabaseCount('pengajuan_tagihan_detail', 1);
        $old = $line->lampiran;
        $input['detail'][0]['file'] = UploadedFile::fake()->create('baru.pdf', 10, 'application/pdf');
        $this->put(route('pengajuan-tagihan.update', 1), $input)->assertRedirect();
        Storage::disk('local')->assertMissing($old);
        $path = $line->fresh()->lampiran;
        $this->delete(route('pengajuan-tagihan.destroy', 1))->assertRedirect();
        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseCount('pengajuan_tagihan_detail', 0);
        $this->assertDatabaseCount('pengajuan_tagihan', 0);
    }

    public function test_modal_accepts_one_bill_at_a_time_and_preserves_other_bills(): void
    {
        $this->get(route('pengajuan-tagihan.index'))->assertOk()->assertSee('open-tagihan')->assertSee('tagihanModal');
        $this->get(route('pengajuan-tagihan.create'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertSee('col-sm-4 col-form-label')->assertSee('modal-header')
            ->assertDontSee('id="addLine"', false)->assertDontSee('<table', false)->assertDontSee('<html', false);
        $this->postJson(route('pengajuan-tagihan.store'), $this->payload())->assertOk()
            ->assertJsonPath('redirect', route('pengajuan-tagihan.index'));
        $first = PengajuanTagihan::first()->detail()->first();
        $input = $this->payload();
        $input['detail'][0]['nama'] = 'Sumber Rejeki';
        $this->putJson(route('pengajuan-tagihan.update', 1), $input)->assertOk();
        $this->assertDatabaseCount('pengajuan_tagihan', 1);
        $this->assertDatabaseCount('pengajuan_tagihan_detail', 2);
        $second = PengajuanTagihan::first()->detail()->reorder('id', 'desc')->first();
        $this->get(route('pengajuan-tagihan.edit', ['pengajuan_tagihan' => 1, 'detail' => $second->id]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertSee('Sumber Rejeki')->assertDontSee('Barokah Jaya');
        $this->get(route('pengajuan-tagihan.edit', ['pengajuan_tagihan' => 1, 'baru' => 1]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertDontSee('Barokah Jaya')->assertDontSee('Sumber Rejeki');
        $input['detail'][0]['id'] = $second->id;
        $input['detail'][0]['nama'] = 'Nama diubah';
        $this->putJson(route('pengajuan-tagihan.update', 1), $input)->assertOk();
        $this->assertSame('Nama diubah', $second->fresh()->nama);
        $this->assertSame('Barokah Jaya', $first->fresh()->nama);
        $this->assertDatabaseCount('pengajuan_tagihan_detail', 2);
        $input['detail'][] = $input['detail'][0];
        unset($input['detail'][1]['id']);
        $this->putJson(route('pengajuan-tagihan.update', 1), $input)->assertUnprocessable()->assertJsonValidationErrors('detail');
        $this->assertDatabaseCount('pengajuan_tagihan_detail', 2);
    }

    public function test_listing_shows_complete_bill_rows_and_scoped_delete(): void
    {
        $input = $this->payload();
        $input['detail'][0]['file'] = UploadedFile::fake()->create('nota.pdf', 20, 'application/pdf');
        $this->postJson(route('pengajuan-tagihan.store'), $input)->assertOk()
            ->assertJsonPath('redirect', route('pengajuan-tagihan.index'));
        $first = PengajuanTagihan::first()->detail()->first();
        $input = $this->payload();
        $input['detail'][0]['nama'] = 'Tagihan kedua';
        $this->putJson(route('pengajuan-tagihan.update', 1), $input)->assertOk();
        $this->get(route('pengajuan-tagihan.index'))->assertOk()->assertSee('tagihanTable')
            ->assertSee('statusFilter')->assertSee('Barokah Jaya')->assertSee('Tagihan kedua')
            ->assertSee('Pipa, besi, lem, kabel')->assertSee('BCA 123456')->assertSee('21.329.000')
            ->assertSee('View Lampiran')->assertSee('tagihanAttachmentModal')->assertSee('Nomor / Tanggal pengajuan')
            ->assertDontSee('Tgl nota max')->assertDontSee('Buka Pengajuan')->assertDontSee('Jumlah Tagihan');
        $this->get(route('pengajuan-tagihan.attachment', ['id' => 1, 'line' => $first->id, 'view' => 1]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $path = $first->lampiran;
        $this->delete(route('pengajuan-tagihan.destroy', ['pengajuan_tagihan' => 1, 'detail' => $first->id]))
            ->assertRedirect(route('pengajuan-tagihan.index'));
        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseCount('pengajuan_tagihan', 1);
        $this->assertDatabaseCount('pengajuan_tagihan_detail', 1);
        $this->get(route('pengajuan-tagihan.index'))->assertOk()->assertSee('Tagihan kedua')->assertDontSee('Barokah Jaya');
    }

    public function test_pdf_uses_filtered_rows_in_table_order_and_landscape(): void
    {
        $this->postJson(route('pengajuan-tagihan.store'), $this->payload())->assertOk();
        $first = PengajuanTagihan::first()->detail()->first();
        $input = $this->payload();
        $input['nomor'] = 'PT-OTHER';
        $input['status'] = 'Dibayar';
        $input['detail'][0]['nama'] = 'Tidak dicetak';
        $this->postJson(route('pengajuan-tagihan.store'), $input)->assertOk();
        $other = PengajuanTagihan::findOrFail(2)->detail()->first();
        $this->get(route('pengajuan-tagihan.index'))->assertOk()->assertSee('printTagihan')->assertSee('Cetak PDF');
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')->once()->withArgs(function ($view, $values) use ($first) {
            $this->assertSame('admin.pengajuan_tagihan.pdf', $view);
            $this->assertSame([$first->id], $values['lines']->pluck('id')->all());
            $this->assertSame('Diajukan', $values['status']);
            $this->assertSame('Barokah', $values['search']);
            $html = view($view, $values)->render();
            $this->assertStringContainsString('21.329.000', $html);
            $this->assertStringNotContainsString('Tidak dicetak', $html);

            return true;
        })->andReturn($pdf);
        $pdf->shouldReceive('setPaper')->once()->with('a4', 'landscape')->andReturnSelf();
        $pdf->shouldReceive('stream')->once()->andReturn(response('%PDF-test', 200, ['Content-Type' => 'application/pdf']));
        $this->post(route('pengajuan-tagihan.pdf'), ['ids' => [$other->id, $first->id], 'status' => 'Diajukan', 'search' => 'Barokah'])
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pdf_empty_results_and_access_control(): void
    {
        $this->postJson(route('pengajuan-tagihan.store'), $this->payload())->assertOk();
        $response = $this->post(route('pengajuan-tagihan.pdf'), ['status' => 'Dibayar']);
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->postJson(route('pengajuan-tagihan.pdf'), ['status' => 'unknown'])->assertUnprocessable();
        DB::table('hak_akses')->update(['lihat' => 0]);
        $this->post(route('pengajuan-tagihan.pdf'))->assertForbidden();
    }

    public function test_invalid_money_status_upload_and_foreign_detail_are_rejected(): void
    {
        foreach (['-1', '1.5', '2.500.000', 'abc', '1000000000000'] as $bad) {
            $input = $this->payload();
            $input['detail'][0]['ruko'] = $bad;
            $this->postJson(route('pengajuan-tagihan.store'), $input)->assertUnprocessable()->assertJsonValidationErrors('detail.0.ruko');
        }
        $input = $this->payload();
        $input['status'] = 'unknown';
        $this->postJson(route('pengajuan-tagihan.store'), $input)->assertUnprocessable()->assertJsonValidationErrors('status');
        $input = $this->payload();
        $input['detail'][0]['file'] = UploadedFile::fake()->create('evil.php', 1, 'text/plain');
        $this->postJson(route('pengajuan-tagihan.store'), $input)->assertUnprocessable()->assertJsonValidationErrors('detail.0.file');
        $this->post(route('pengajuan-tagihan.store'), $this->payload())->assertRedirect();
        $input = $this->payload();
        $input['detail'][0]['id'] = 999;
        $this->putJson(route('pengajuan-tagihan.update', 1), $input)->assertUnprocessable();
        $this->assertDatabaseCount('pengajuan_tagihan_detail', 1);
    }

    public function test_access_is_granted_to_nita_and_enforced_for_every_action(): void
    {
        $this->assertSame(1, DB::table('hak_akses')->where('id_user', 1)->count());
        $this->assertSame(0, DB::table('hak_akses')->where('id_user', 2)->count());
        $this->post(route('pengajuan-tagihan.store'), $this->payload())->assertRedirect();
        $input = $this->payload();
        $input['detail'][0]['file'] = UploadedFile::fake()->create('nota.pdf', 1, 'application/pdf');
        $this->put(route('pengajuan-tagihan.update', 1), $input)->assertRedirect();
        $line = PengajuanTagihan::first()->detail()->first();
        DB::table('hak_akses')->update(['tambah' => 0, 'edit' => 0, 'hapus' => 0]);
        $this->get(route('pengajuan-tagihan.create'))->assertForbidden();
        $this->post(route('pengajuan-tagihan.store'), $this->payload())->assertForbidden();
        $this->get(route('pengajuan-tagihan.edit', 1))->assertForbidden();
        $this->put(route('pengajuan-tagihan.update', 1), $this->payload())->assertForbidden();
        $this->delete(route('pengajuan-tagihan.destroy', 1))->assertForbidden();
        DB::table('hak_akses')->update(['lihat' => 0]);
        $this->get(route('pengajuan-tagihan.index'))->assertForbidden();
        $this->get(route('pengajuan-tagihan.show', 1))->assertForbidden();
        $this->get(route('pengajuan-tagihan.attachment', [1, $line->id]))->assertForbidden();
    }
}
