<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BastPdfTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('customer', function (Blueprint $t) {
            $t->id();
            $t->string('nama_lengkap');
            $t->integer('stt_arsip');
            $t->integer('id_lokasi')->nullable();
            $t->integer('id_kavling')->nullable();
        });
        Schema::create('lokasi_kavling', function (Blueprint $t) {
            $t->id();
            $t->string('nama_kavling');
        });
        Schema::create('kavling_peta', function (Blueprint $t) {
            $t->id();
            $t->string('nama_cluster');
            $t->string('kode_kavling');
        });
        Schema::create('bast', function (Blueprint $t) {
            $t->id();
            $t->integer('id_customer');
            $t->date('tanggal_bast');
            $t->string('no_bast');
        });
        Schema::create('menu', function (Blueprint $t) {
            $t->id();
            $t->string('route_name');
        });
        Schema::create('hak_akses', function (Blueprint $t) {
            $t->id();
            $t->integer('id_user');
            $t->integer('id_menu');
            $t->integer('lihat');
        });
        DB::table('menu')->insert(['id' => 1, 'route_name' => 'bast.index']);
        DB::table('hak_akses')->insert(['id_user' => 1, 'id_menu' => 1, 'lihat' => 1]);
        DB::table('customer')->insert([['id' => 1, 'nama_lengkap' => 'Aktif', 'stt_arsip' => 0], ['id' => 2, 'nama_lengkap' => 'Arsip', 'stt_arsip' => 1]]);
        DB::table('bast')->insert([['id' => 1, 'id_customer' => 1, 'tanggal_bast' => '2026-11-01', 'no_bast' => 'BAST-001'], ['id' => 2, 'id_customer' => 2, 'tanggal_bast' => '2026-11-02', 'no_bast' => 'BAST-002']]);
    }

    public function test_pdf_uses_filtered_active_rows_and_landscape(): void
    {
        $user = new User;
        $user->id = 1;
        $this->actingAs($user);
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')->once()->withArgs(function ($view, $values) {
            $this->assertSame([1], $values['items']->pluck('id')->all());
            $html = view($view, $values)->render();
            $this->assertStringContainsString('BAST-001', $html);
            $this->assertStringNotContainsString('BAST-002', $html);

            return true;
        })->andReturn($pdf);
        $pdf->shouldReceive('setPaper')->once()->with('a4', 'landscape')->andReturnSelf();
        $pdf->shouldReceive('stream')->once()->andReturn(response('%PDF-test', 200, ['Content-Type' => 'application/pdf']));
        $this->post(route('bast.pdf'), ['filtered' => 1, 'ids' => [2, 1], 'search' => 'Aktif'])->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_empty_pdf_and_access_control(): void
    {
        $this->post(route('bast.pdf'))->assertRedirect(route('login'));
        $user = new User;
        $user->id = 1;
        $this->actingAs($user);
        $result = $this->post(route('bast.pdf'), ['filtered' => 1])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $result->getContent());
        DB::table('hak_akses')->update(['lihat' => 0]);
        $this->post(route('bast.pdf'))->assertForbidden();
    }
}
