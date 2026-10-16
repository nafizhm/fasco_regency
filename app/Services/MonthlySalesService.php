<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class MonthlySalesService
{
    public function forYear(int $year): array
    {
        $start = sprintf('%04d-01-01', $year);
        $end = sprintf('%04d-01-01', $year + 1);
        $months = array_fill(1, 12, ['booking' => 0, 'akad' => 0]);
        $bookings = DB::table('pengajuan_hold')->where('tgl_booking', '>=', $start)->where('tgl_booking', '<', $end)
            ->whereIn('stt_reg', [1, 2])->pluck('tgl_booking');
        foreach ($bookings as $date) $months[(int) substr($date, 5, 2)]['booking']++;
        $akad = DB::table('detail_akad')->join('akad', 'akad.id', '=', 'detail_akad.id_akad')
            ->where('detail_akad.status', 2)->where('akad.tgl_akad', '>=', $start)->where('akad.tgl_akad', '<', $end)
            ->select('akad.id', 'akad.tgl_akad', 'detail_akad.id_customer')->distinct()->get();
        foreach ($akad as $row) $months[(int) substr($row->tgl_akad, 5, 2)]['akad']++;
        return $months;
    }
}
