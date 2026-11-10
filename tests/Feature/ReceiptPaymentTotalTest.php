<?php

namespace Tests\Feature;

use App\Models\Pemasukan;
use App\Services\ReceiptPaymentTotal;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReceiptPaymentTotalTest extends TestCase
{
    public function test_total_groups_exact_receipt_prefix_for_the_same_customer(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('pemasukan', function (Blueprint $table) {
            $table->id();
            $table->integer('id_customer');
            $table->string('no_kwitansi')->nullable();
            $table->integer('nominal');
            $table->string('keterangan')->nullable();
        });
        foreach ([
            [1, '004-B/NHM/2026', 100, null],
            [1, '004-B/OTHER/2026', 200, 'DP'],
            [1, '004-B1/NHM/2026', 400, null],
            [2, '004-B/NHM/2026', 800, null],
            [1, '004-B/NHM/2026', 1600, 'Biaya ganti nama customer'],
            [1, null, 50, null],
        ] as [$customer, $number, $amount, $description]) {
            DB::table('pemasukan')->insert(['id_customer' => $customer, 'no_kwitansi' => $number,
                'nominal' => $amount, 'keterangan' => $description]);
        }
        $service = new ReceiptPaymentTotal;
        $this->assertSame(300.0, $service->total(Pemasukan::findOrFail(1)));
        $this->assertSame(50.0, $service->total(Pemasukan::findOrFail(6)));
    }
}
