<?php

namespace App\Http\Controllers;

use App\Services\RekapTransaksi;
use App\Traits\InventoryAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class RekapTransaksiController extends Controller
{
    use InventoryAccess;

    public function index(Request $request, RekapTransaksi $service)
    {
        $this->authorizeInventory('rekap-transaksi.index');
        $filters = $request->validate([
            'tanggal_awal' => 'required_with:preview,format|date_format:Y-m-d',
            'tanggal_akhir' => 'required_with:preview,format|date_format:Y-m-d|after_or_equal:tanggal_awal',
            'tampilan' => 'sometimes|required|in:ringkasan,detail',
            'preview' => 'sometimes|required|in:1',
            'format' => 'sometimes|required|in:pdf',
        ]);
        $start = $filters['tanggal_awal'] ?? now('Asia/Jakarta')->startOfMonth()->toDateString();
        $end = $filters['tanggal_akhir'] ?? now('Asia/Jakarta')->toDateString();
        $mode = $filters['tampilan'] ?? 'ringkasan';
        $report = isset($filters['preview']) || isset($filters['format']) ? $service->build($start, $end, $mode) : null;
        if (isset($filters['format'])) {
            return Pdf::loadView('admin.rekap_transaksi.pdf', compact('report'))
                ->setPaper('a4', $mode === 'detail' ? 'landscape' : 'portrait')
                ->stream('rekap-transaksi-'.$mode.'-'.$start.'-'.$end.'.pdf');
        }
        return view('admin.rekap_transaksi.index', compact('start', 'end', 'mode', 'report'));
    }
}
