<?php

namespace App\Services;

use App\Models\KavlingPeta;

class SiteplanColors
{
    public const CLUSTERS = [
        'JINGGA' => '#f97316', 'BIRU' => '#3b82f6', 'HIJAU' => '#22c55e',
        'KUNING' => '#facc15', 'PUTIH' => '#ffffff', 'MERAH' => '#ef4444',
    ];

    public static function cluster(KavlingPeta $unit): string
    {
        return self::CLUSTERS[strtoupper(trim($unit->nama_cluster ?? ''))] ?? '#ffffff';
    }

    public static function sales(KavlingPeta $unit, string $holdColor, bool $whiteReady = false): string
    {
        if ($unit->customer) return $unit->customer->progres?->warna ?: '#111827';
        return $unit->is_booked ? $holdColor : ($whiteReady ? '#ffffff' : self::cluster($unit));
    }
}
