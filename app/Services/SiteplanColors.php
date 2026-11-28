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
        $status = self::status($unit);
        if ($status === 'ready') return $whiteReady ? '#ffffff' : self::cluster($unit);
        $colors = app()->bound('siteplan.sales.colors') ? app('siteplan.sales.colors') : self::colors();
        return $colors[$status];
    }

    public static function status(KavlingPeta $unit): string
    {
        return $unit->siteplan_status ?: ($unit->customer ? 'sold' : ($unit->is_booked ? 'proses' : 'ready'));
    }

    public static function label(KavlingPeta $unit): string
    {
        $status = self::status($unit);
        return $status === 'ready' ? $unit->kode_kavling : strtoupper($status);
    }

    public static function colors(): array
    {
        $colors = array_replace(['proses' => '#facc15', 'sold' => '#ef4444'],
            \Illuminate\Support\Facades\DB::table('siteplan_sales_colors')->pluck('color', 'status')->all());
        app()->instance('siteplan.sales.colors', $colors);
        return $colors;
    }

    public static function canManage(): bool
    {
        return strtolower(trim(auth()->user()?->username ?? '')) === 'safira';
    }
}
