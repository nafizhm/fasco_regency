<?php

namespace Tests\Feature;

use App\Models\InvoiceIpl;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceIplTest extends TestCase
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
        (require database_path('migrations/2026_11_28_010000_create_invoice_ipl.php'))->up();
        $user = new User;
        $user->id = 1;
        $user->username = 'nita';
        $this->actingAs($user);
        Storage::fake('local');
    }

    private function payload(): array
    {
        return ['nomor' => 'IPL-TEST', 'nama' => 'Habibur Rahman', 'alamat' => 'Perumahan Fasco Regency Blok Hijau 4',
            'tanggal_stu' => '2026-01-10', 'nominal_tahun' => '3000000', 'jatuh_tempo' => '2027-01-10',
            'nama_rekening' => 'Laji Siswanto', 'bank' => 'BCA', 'no_rekening' => '3681655211',
            'penandatangan' => 'Marianita', 'jabatan' => 'Staff Administrasi',
            'pembayaran' => [
                ['tanggal' => '2026-02-13', 'nominal' => '250000', 'transaksi' => 'Transfer', 'keterangan' => 'Bayar Februari'],
                ['tanggal' => '2026-03-27', 'nominal' => '250000', 'transaksi' => 'Transfer', 'keterangan' => 'Bayar Maret'],
            ]];
    }

    public function test_crud_totals_separate_pages_and_pdf(): void
    {
        $this->get(route('invoice-ipl.index'))->assertOk()->assertSee('Tambah Invoice IPL');
        $this->get(route('invoice-ipl.create'))->assertOk()->assertSee('inputmode="numeric"', false)->assertViewIs('admin.invoice_ipl.form');
        $this->post(route('invoice-ipl.store'), $this->payload())->assertRedirect(route('invoice-ipl.index'));
        $data = InvoiceIpl::with('pembayaran')->firstOrFail();
        $this->assertSame(500000, $data->total_pembayaran);
        $this->assertSame(2500000, $data->sisa);
        $this->assertSame('Sebagian', $data->status);
        $this->get(route('invoice-ipl.show', 1))->assertOk()->assertSee('Habibur Rahman')->assertSee('Rp500.000')->assertSee('Marianita');
        $this->get(route('invoice-ipl.edit', 1))->assertOk()->assertSee('Bayar Februari')->assertSee('250000')->assertViewIs('admin.invoice_ipl.form');
        $pdf = $this->get(route('invoice-ipl.pdf', 1))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $input = $this->payload();
        $input['pembayaran'] = [['tanggal' => '2026-04-01', 'nominal' => '3000000', 'transaksi' => 'Tunai']];
        $this->put(route('invoice-ipl.update', 1), $input)->assertRedirect(route('invoice-ipl.index'));
        $this->assertSame('Lunas', $data->fresh()->status);
        $this->assertDatabaseCount('invoice_ipl_pembayaran', 1);
        unset($input['pembayaran']);
        $this->put(route('invoice-ipl.update', 1), $input)->assertRedirect();
        $this->assertSame('Belum Dibayar', $data->fresh()->status);
        $this->assertDatabaseCount('invoice_ipl_pembayaran', 0);
        $this->delete(route('invoice-ipl.destroy', 1))->assertRedirect();
        $this->assertDatabaseCount('invoice_ipl', 0);
    }

    public function test_company_details_are_fixed_and_not_form_fields(): void
    {
        $input = $this->payload();
        foreach (InvoiceIpl::PERUSAHAAN as $key => $value) {
            unset($input[$key]);
        }
        $this->get(route('invoice-ipl.create'))->assertOk()->assertDontSee('name="nama_rekening"', false)
            ->assertDontSee('name="bank"', false)->assertDontSee('name="no_rekening"', false)
            ->assertDontSee('name="penandatangan"', false)->assertDontSee('name="jabatan"', false);
        $this->post(route('invoice-ipl.store'), $input)->assertRedirect();
        $record = InvoiceIpl::firstOrFail();
        foreach (InvoiceIpl::PERUSAHAAN as $key => $value) {
            $this->assertSame($value, $record->$key);
        }
        $input['nama_rekening'] = 'Rekening lain';
        $input['no_rekening'] = '999';
        $input['penandatangan'] = 'Orang lain';
        $this->put(route('invoice-ipl.update', $record->id), $input)->assertRedirect();
        $this->assertSame('Laji Siswanto', $record->fresh()->nama_rekening);
        $this->assertSame('3681655211', $record->fresh()->no_rekening);
        $record->update(['nama_rekening' => 'Data lama', 'penandatangan' => 'Nama lama']);
        $this->get(route('invoice-ipl.show', $record->id))->assertOk()->assertSee('Laji Siswanto')->assertSee('Marianita')->assertDontSee('Data lama')->assertDontSee('Nama lama');
        $this->get(route('invoice-ipl.edit', $record->id))->assertOk()->assertDontSee('name="nama_rekening"', false);
    }

    public function test_money_dates_and_duplicate_invoice_validation(): void
    {
        foreach (['-1', '1.5', '3.000.000', 'abc', '1000000000000'] as $bad) {
            $input = $this->payload();
            $input['nominal_tahun'] = $bad;
            $this->postJson(route('invoice-ipl.store'), $input)->assertUnprocessable()->assertJsonValidationErrors('nominal_tahun');
            $input = $this->payload();
            $input['pembayaran'][0]['nominal'] = $bad;
            $this->postJson(route('invoice-ipl.store'), $input)->assertUnprocessable()->assertJsonValidationErrors('pembayaran.0.nominal');
        }
        $input = $this->payload();
        $input['jatuh_tempo'] = '2025-01-01';
        $this->postJson(route('invoice-ipl.store'), $input)->assertUnprocessable()->assertJsonValidationErrors('jatuh_tempo');
        $this->post(route('invoice-ipl.store'), $this->payload())->assertRedirect();
        $this->postJson(route('invoice-ipl.store'), $this->payload())->assertUnprocessable()->assertJsonValidationErrors('nomor');
    }

    public function test_nita_permissions_and_other_users_denied(): void
    {
        $this->assertSame(1, DB::table('hak_akses')->where('id_user', 1)->count());
        $this->assertSame(0, DB::table('hak_akses')->where('id_user', 2)->count());
        $this->post(route('invoice-ipl.store'), $this->payload())->assertRedirect();
        DB::table('hak_akses')->update(['tambah' => 0, 'edit' => 0, 'hapus' => 0]);
        $this->get(route('invoice-ipl.create'))->assertForbidden();
        $this->post(route('invoice-ipl.store'), $this->payload())->assertForbidden();
        $this->get(route('invoice-ipl.edit', 1))->assertForbidden();
        $this->put(route('invoice-ipl.update', 1), $this->payload())->assertForbidden();
        $this->delete(route('invoice-ipl.destroy', 1))->assertForbidden();
        DB::table('hak_akses')->update(['lihat' => 0]);
        $this->get(route('invoice-ipl.index'))->assertForbidden();
        $this->get(route('invoice-ipl.show', 1))->assertForbidden();
        $this->get(route('invoice-ipl.pdf', 1))->assertForbidden();
    }
}
