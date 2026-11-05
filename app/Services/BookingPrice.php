<?php

namespace App\Services;

use App\Models\KavlingPeta;
use Illuminate\Validation\ValidationException;

class BookingPrice
{
    public static function breakdown(KavlingPeta $unit, int $discount = 0): array
    {
        $items = $unit->rincian_biaya ?? [];
        return self::fromItems($items, $discount);
    }

    public static function fromItems(array $items, int $discount = 0): array
    {
        $houseIndex = collect($items)->search(fn ($item) => ($item['nama'] ?? '') === 'Harga Rumah');
        $housePrice = $houseIndex === false ? 0 : (int) $items[$houseIndex]['nilai'];
        if ($discount < 0 || $discount > $housePrice) {
            throw ValidationException::withMessages(['diskon' => 'Diskon harus antara Rp0 dan harga rumah sebelum diskon.']);
        }
        if ($houseIndex !== false) $items[$houseIndex]['nilai'] = $housePrice - $discount;
        if (collect($items)->sum('nilai') <= 0) {
            throw ValidationException::withMessages(['diskon' => 'Total harga setelah diskon harus lebih dari Rp0.']);
        }

        return $items;
    }
}
