<?php

namespace Tests\Feature;

use App\Models\KavlingPeta;
use App\Services\BookingPrice;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingDiscountTest extends TestCase
{
    private function unit(): KavlingPeta
    {
        return new KavlingPeta(['rincian_biaya' => [
            ['nama' => 'Harga Rumah', 'nilai' => 150000000],
            ['nama' => 'Biaya Surat', 'nilai' => 5000000],
        ]]);
    }

    public function test_discount_only_reduces_house_price_and_preserves_unit_price(): void
    {
        $unit = $this->unit();
        $items = BookingPrice::breakdown($unit, 10000000);
        $this->assertSame(140000000, $items[0]['nilai']);
        $this->assertSame(5000000, $items[1]['nilai']);
        $this->assertEquals(145000000, collect($items)->sum('nilai'));
        $this->assertEquals(155000000, $unit->total_harga);
        $this->assertSame($unit->rincian_biaya, BookingPrice::breakdown($unit));
    }

    public function test_discount_above_house_price_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        BookingPrice::breakdown($this->unit(), 150000001);
    }

    public function test_negative_discount_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        BookingPrice::breakdown($this->unit(), -1);
    }

    public function test_zero_net_total_is_rejected(): void
    {
        $unit = new KavlingPeta(['rincian_biaya' => [['nama' => 'Harga Rumah', 'nilai' => 100]]]);
        $this->expectException(ValidationException::class);
        BookingPrice::breakdown($unit, 100);
    }
}
