<?php

namespace App\Http\Controllers\Pengaturan;

use App\Http\Controllers\Controller;
use App\Services\LaporanTukang;
use App\Traits\InventoryAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class LaporanTukangController extends Controller
{
    use InventoryAccess;

    public function index(Request $request, LaporanTukang $service)
    {
        $this->authorizeInventory('laporan-tukang.index');
        $input = $request->validate([
            'jenis' => 'nullable|in:mingguan,bulanan',
            'minggu' => 'nullable|date_format:Y-m-d',
            'bulan' => 'nullable|date_format:Y-m',
            'preview' => 'nullable|in:1',
            'format' => 'nullable|in:pdf',
        ]);
        $type = $input['jenis'] ?? 'mingguan';
        $week = $input['minggu'] ?? now('Asia/Jakarta')->startOfWeek()->toDateString();
        $month = $input['bulan'] ?? now('Asia/Jakarta')->format('Y-m');
        $report = null;
        if (isset($input['preview']) || isset($input['format'])) {
            $report = $service->build($type, $type === 'mingguan' ? $week : $month.'-01');
        }
        if (isset($input['format'])) {
            $period = $type === 'mingguan' ? $report['columns'][0]['start']->toDateString() : $month;
            return Pdf::loadView('admin.pengaturan.tukang.laporan-pdf', compact('report'))
                ->setPaper('a4', $type === 'bulanan' ? 'portrait' : 'landscape')
                ->stream('laporan-tukang-'.$type.'-'.$period.'.pdf');
        }
        return view('admin.pengaturan.tukang.laporan', compact('type', 'week', 'month', 'report'));
    }
}
