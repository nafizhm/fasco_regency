<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TukangAttendanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        (require database_path('migrations/2026_11_10_010000_restore_inventory_modules.php'))->up();
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
        DB::table('menu')->insert(['id' => 1, 'title' => 'Data Tukang', 'route_name' => 'tukang.index', 'icon' => 'fas fa-users']);
        DB::table('hak_akses')->insert(['id_user' => 1, 'id_menu' => 1]);
        (require database_path('migrations/2026_11_14_010000_add_tukang_attendance.php'))->up();
        DB::table('tukang')->where('id', 1)->update(['gaji_per_hari' => 150000]);
        $user = new User(); $user->id = 1; $user->username = 'test'; $this->actingAs($user);
    }

    private function payload(): array
    {
        return ['mulai' => '2026-11-09', 'versi' => 0,
            'rows' => [['id' => 1, 'hari' => ['07:30', 'M', '½', 'I', 'S', '']]]];
    }

    public function test_week_is_saved_reopened_and_rate_is_preserved(): void
    {
        $this->postJson(route('absen-tukang.store'), $this->payload())->assertOk();
        $this->assertDatabaseHas('absen_tukang_detail', ['id_tukang' => 1, 'gaji_per_hari' => 150000]);
        $this->get(route('absen-tukang.index', ['minggu' => '2026-11-12']))->assertOk()
            ->assertSee('value="07:30"', false)->assertSee('150.000')->assertSee('Rekap pekan sudah tersimpan.');
        DB::table('tukang')->where('id', 1)->update(['gaji_per_hari' => 200000]);
        $payload = $this->payload(); $payload['versi'] = 1; $payload['rows'][0]['hari'][5] = 'M';
        $this->postJson(route('absen-tukang.store'), $payload)->assertOk();
        $this->assertDatabaseHas('absen_tukang_detail', ['id_tukang' => 1, 'gaji_per_hari' => 150000]);
        $this->assertDatabaseHas('absen_tukang_pekan', ['mulai' => '2026-11-09', 'versi' => 2]);
    }

    public function test_invalid_times_duplicate_workers_and_stale_edits_are_rejected(): void
    {
        $payload = $this->payload(); $payload['rows'][0]['hari'][0] = '25:70';
        $this->postJson(route('absen-tukang.store'), $payload)->assertUnprocessable();
        $payload = $this->payload(); $payload['rows'][] = $payload['rows'][0];
        $this->postJson(route('absen-tukang.store'), $payload)->assertUnprocessable();
        $this->assertDatabaseCount('absen_tukang_pekan', 0);
        $this->postJson(route('absen-tukang.store'), $this->payload())->assertOk();
        $this->postJson(route('absen-tukang.store'), $this->payload())->assertUnprocessable()->assertJsonValidationErrors('versi');
        $this->assertDatabaseHas('absen_tukang_pekan', ['versi' => 1]);
    }

    public function test_attendance_permissions_and_historical_worker_deletion_are_enforced(): void
    {
        $menu = DB::table('menu')->where('route_name', 'absen-tukang.index')->value('id');
        DB::table('hak_akses')->where('id_menu', $menu)->update(['tambah' => 0]);
        $this->postJson(route('absen-tukang.store'), $this->payload())->assertForbidden();
        DB::table('hak_akses')->where('id_menu', $menu)->update(['tambah' => 1, 'edit' => 0]);
        $this->postJson(route('absen-tukang.store'), $this->payload())->assertOk();
        $payload = $this->payload(); $payload['versi'] = 1;
        $this->postJson(route('absen-tukang.store'), $payload)->assertForbidden();
        $this->deleteJson(route('tukang.destroy', 1))->assertUnprocessable();
        $this->assertDatabaseHas('tukang', ['id' => 1]);
    }

    public function test_worker_modal_payload_saves_salary_and_reference_import_is_repeatable(): void
    {
        $count = DB::table('tukang')->count();
        (new \Database\Seeders\TukangReferenceSeeder)->run();
        $this->assertDatabaseCount('tukang', $count);
        $this->postJson(route('tukang.store'), ['nama' => 'Test Tukang', 'no_telp' => '08123456789', 'gaji_per_hari' => 125000])->assertOk();
        $id = DB::table('tukang')->where('nama', 'Test Tukang')->value('id');
        $this->putJson(route('tukang.update', $id), ['nama' => 'Test Tukang', 'no_telp' => '08123456789', 'gaji_per_hari' => 175000])->assertOk();
        $this->assertDatabaseHas('tukang', ['id' => $id, 'gaji_per_hari' => 175000]);
        $this->putJson(route('tukang.update', $id), ['nama' => 'Test Tukang', 'gaji_per_hari' => -1])->assertUnprocessable();
    }
}
