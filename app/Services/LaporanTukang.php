<?php

namespace App\Services;

use App\Models\Tukang;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class LaporanTukang
{
    public function build(string $type, string $period): array
    {
        $date = CarbonImmutable::parse($period)->locale('id');
        $starts = [];
        if ($type === 'mingguan') {
            $starts[] = $date->startOfWeek();
            $title = 'Laporan Mingguan Tukang';
            $label = $starts[0]->translatedFormat('d F Y').' - '.$starts[0]->addDays(5)->translatedFormat('d F Y');
        } else {
            $month = $date->startOfMonth();
            // Satu pekan dibayar pada Sabtu; tidak dihitung ulang pada bulan berikutnya.
            $firstSaturday = $month->addDays((6 - $month->dayOfWeek + 7) % 7);
            for ($saturday = $firstSaturday; $saturday->lte($month->endOfMonth()); $saturday = $saturday->addWeek()) {
                $starts[] = $saturday->subDays(5);
            }
            $title = 'Laporan Pembayaran Tukang Bulanan';
            $label = $month->translatedFormat('F Y');
        }

        $weeks = DB::table('absen_tukang_pekan')->whereIn('mulai', array_map(fn ($start) => $start->toDateString(), $starts))->get()->keyBy('mulai');
        $details = DB::table('absen_tukang_detail')->whereIn('id_pekan', $weeks->pluck('id'))->get()->keyBy(fn ($row) => $row->id_tukang.':'.$row->id_pekan);
        $columns = array_map(function ($start) use ($weeks) {
            return ['start' => $start, 'end' => $start->addDays(5), 'saved' => isset($weeks[$start->toDateString()])];
        }, $starts);
        $totals = array_fill(0, count($starts), 0);
        $rows = [];
        foreach (Tukang::orderBy('id')->get() as $worker) {
            $cells = [];
            foreach ($starts as $i => $start) {
                $week = $weeks->get($start->toDateString());
                $detail = $week ? $details->get($worker->id.':'.$week->id) : null;
                $days = $detail ? json_decode($detail->hari, true, 512, JSON_THROW_ON_ERROR) : array_fill(0, 6, '');
                $count = array_sum(array_map(fn ($value) => $this->units($value), $days));
                $rate = (int) ($detail->gaji_per_hari ?? 0);
                $bonus = (int) ($detail->tambahan_gaji ?? 0);
                $wage = $count * $rate + $bonus;
                $cells[] = ['days' => $days, 'count' => $count, 'rate' => $rate, 'bonus' => $bonus, 'wage' => $wage, 'saved' => $detail !== null];
                $totals[$i] += $wage;
            }
            $rows[] = ['name' => $worker->nama, 'cells' => $cells];
        }

        return compact('type', 'title', 'label', 'columns', 'rows', 'totals') + ['grandTotal' => array_sum($totals)];
    }

    private function units(?string $value): float
    {
        $value = strtoupper(trim($value ?? ''));
        if (in_array($value, ['0.5', '½', 'Â½'], true)) return 0.5;
        return in_array($value, ['M', 'H'], true) || preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $value) ? 1 : 0;
    }
}
