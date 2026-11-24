<?php

namespace Tests\Feature;

use App\Models\Pemasukan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReceiptVisibilityTest extends TestCase
{
    public function test_visibility_defaults_to_shown_and_only_filters_receipt_history(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('pemasukan', function (Blueprint $table) {
            $table->id();
            $table->integer('nominal');
        });
        DB::table('pemasukan')->insert(['nominal' => 100]);
        $migration = require database_path('migrations/2026_11_24_010000_add_tampilkan_kwitansi_to_pemasukan.php');
        $migration->up();
        $this->assertTrue(Pemasukan::findOrFail(1)->tampilkan_kwitansi);
        $hidden = Pemasukan::create(['nominal' => 200, 'tampilkan_kwitansi' => false]);
        $shown = Pemasukan::create(['nominal' => 300]);
        $this->assertSame([1, $shown->id], Pemasukan::tampilDiKwitansi()->pluck('id')->all());
        $this->assertEquals(400, Pemasukan::tampilDiKwitansi()->sum('nominal'));
        $this->assertEquals(600, Pemasukan::sum('nominal'));
        $hidden->update(['tampilkan_kwitansi' => true]);
        $this->assertSame(3, Pemasukan::tampilDiKwitansi()->count());
    }
}
