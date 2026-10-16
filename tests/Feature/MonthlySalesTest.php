<?php

namespace Tests\Feature;

use App\Services\MonthlySalesService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MonthlySalesTest extends TestCase
{
    public function test_counts_use_booking_and_completed_akad_dates_with_zero_months(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('pengajuan_hold', function (Blueprint $table) { $table->id(); $table->date('tgl_booking'); $table->integer('stt_reg'); });
        Schema::create('akad', function (Blueprint $table) { $table->id(); $table->date('tgl_akad'); });
        Schema::create('detail_akad', function (Blueprint $table) { $table->id(); $table->integer('id_akad'); $table->integer('id_customer'); $table->integer('status'); });
        DB::table('pengajuan_hold')->insert([
            ['tgl_booking' => '2026-01-31', 'stt_reg' => 2],
            ['tgl_booking' => '2026-01-01', 'stt_reg' => 1],
            ['tgl_booking' => '2026-01-10', 'stt_reg' => 3],
            ['tgl_booking' => '2026-02-01', 'stt_reg' => 2],
            ['tgl_booking' => '2025-12-31', 'stt_reg' => 2],
        ]);
        DB::table('akad')->insert([
            ['id' => 1, 'tgl_akad' => '2026-02-01'], ['id' => 2, 'tgl_akad' => '2025-12-31'],
        ]);
        DB::table('detail_akad')->insert([
            ['id_akad' => 1, 'id_customer' => 1, 'status' => 2],
            ['id_akad' => 1, 'id_customer' => 1, 'status' => 2],
            ['id_akad' => 1, 'id_customer' => 2, 'status' => 2],
            ['id_akad' => 1, 'id_customer' => 3, 'status' => 1],
            ['id_akad' => 2, 'id_customer' => 4, 'status' => 2],
        ]);
        $service = app(MonthlySalesService::class);
        $months = $service->forYear(2026);
        $this->assertCount(12, $months);
        $this->assertSame(['booking' => 2, 'akad' => 0], $months[1]);
        $this->assertSame(['booking' => 1, 'akad' => 2], $months[2]);
        $this->assertSame(['booking' => 0, 'akad' => 0], $months[12]);
        $this->assertSame(['booking' => 1, 'akad' => 1], $service->forYear(2025)[12]);
    }
}
