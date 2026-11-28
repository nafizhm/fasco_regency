<?php

namespace Tests\Feature;

use App\Models\RekapKpr;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RekapKprTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('username');
        });
        DB::table('users')->insert([['id' => 1, 'username' => 'Tutut'], ['id' => 2, 'username' => 'other']]);
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
        Schema::create('bank_kpr', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
        });
        DB::table('bank_kpr')->insert(['nama' => 'BRI']);
        (require database_path('migrations/2026_11_28_020000_create_rekap_kpr.php'))->up();
        $user = new User;
        $user->id = 1;
        $user->username = 'Tutut';
        $this->actingAs($user);
        Storage::fake('local');
    }

    private function payload(): array
    {
        return ['bank' => 'bri', 'nama_user' => 'YENI', 'blok' => 'Biru 18', 'tanggal_booking' => '2026-09-30',
            'tanggal_itj' => null, 'tanggal_masuk' => null, 'keterangan' => 'pengembalian', 'perumahan' => 'FR', 'tandai_merah' => '1'];
    }

    public function test_crud_grouping_filters_and_optional_dates(): void
    {
        $this->get(route('rekap-kpr.index'))->assertOk()->assertSee('DATA USER KPR NAUFAL GROUP')->assertSee('USER KPR BRI')->assertSee('USER KPR BSN');
        $this->get(route('rekap-kpr.create'))->assertOk()->assertViewIs('admin.rekap_kpr.form');
        $this->post(route('rekap-kpr.store'), $this->payload())->assertRedirect(route('rekap-kpr.index'));
        $data = RekapKpr::firstOrFail();
        $this->assertSame('BRI', $data->bank);
        $this->assertTrue($data->tandai_merah);
        $this->assertNull($data->tanggal_itj);
        $this->get(route('rekap-kpr.index'))->assertOk()->assertSee('YENI')->assertSee('30/09/2026')->assertSee('pengembalian');
        $this->get(route('rekap-kpr.show', $data->id))->assertOk()->assertSee('Biru 18');
        $this->get(route('rekap-kpr.edit', $data->id))->assertOk()->assertSee('YENI');
        $input = $this->payload();
        $input['bank'] = 'BNI';
        $input['nama_user'] = 'FABIA HULWA';
        $input['tandai_merah'] = '0';
        $this->put(route('rekap-kpr.update', $data->id), $input)->assertRedirect();
        $this->assertSame('BNI', $data->fresh()->bank);
        $this->assertFalse($data->fresh()->tandai_merah);
        $this->get(route('rekap-kpr.index', ['bank' => 'BNI', 'q' => 'FABIA']))->assertOk()->assertSee('FABIA HULWA')->assertDontSee('USER KPR BRI');
        $this->get(route('rekap-kpr.index', ['bank' => 'BRI']))->assertOk()->assertDontSee('FABIA HULWA');
        $this->delete(route('rekap-kpr.destroy', $data->id))->assertRedirect();
        $this->assertDatabaseCount('rekap_kpr', 0);
    }

    public function test_validation_and_crud_permissions(): void
    {
        $input = $this->payload();
        $input['tanggal_itj'] = 'not-a-date';
        $this->postJson(route('rekap-kpr.store'), $input)->assertUnprocessable()->assertJsonValidationErrors('tanggal_itj');
        $input = $this->payload();
        $input['nama_user'] = '';
        $this->postJson(route('rekap-kpr.store'), $input)->assertUnprocessable()->assertJsonValidationErrors('nama_user');
        $this->assertSame(1, DB::table('hak_akses')->where('id_user', 1)->count());
        $this->assertSame(0, DB::table('hak_akses')->where('id_user', 2)->count());
        $this->post(route('rekap-kpr.store'), $this->payload())->assertRedirect();
        DB::table('hak_akses')->update(['tambah' => 0, 'edit' => 0, 'hapus' => 0]);
        $this->get(route('rekap-kpr.create'))->assertForbidden();
        $this->post(route('rekap-kpr.store'), $this->payload())->assertForbidden();
        $this->get(route('rekap-kpr.edit', 1))->assertForbidden();
        $this->put(route('rekap-kpr.update', 1), $this->payload())->assertForbidden();
        $this->delete(route('rekap-kpr.destroy', 1))->assertForbidden();
        DB::table('hak_akses')->update(['lihat' => 0]);
        $this->get(route('rekap-kpr.index'))->assertForbidden();
        $this->get(route('rekap-kpr.show',1))->assertForbidden();
    }
}
