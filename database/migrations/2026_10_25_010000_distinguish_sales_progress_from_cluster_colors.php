<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const COLORS = [
        'hold' => ['#ffc107', '#94a3b8'],
        'booking fee' => ['#0080ff', '#7c3aed'],
        'proses bank' => ['#ff8000', '#0e7490'],
        'sp3k' => ['#008000', '#be185d'],
        'akad' => ['#ff80ff', '#111827'],
        'pembelian cash' => ['#00ffd5', '#6b4f3f'],
    ];

    public function up(): void
    {
        foreach (self::COLORS as $status => [$old, $new]) {
            DB::table('progres_list_penjualan')->whereRaw('LOWER(TRIM(status_progres)) = ?', [$status])
                ->update(['warna' => $new]);
        }
    }

    public function down(): void
    {
        foreach (self::COLORS as $status => [$old, $new]) {
            DB::table('progres_list_penjualan')->whereRaw('LOWER(TRIM(status_progres)) = ?', [$status])
                ->where('warna', $new)->update(['warna' => $old]);
        }
    }
};
