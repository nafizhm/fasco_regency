<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class RiwayatMutasiController extends Controller
{
    use \App\Traits\InventoryAccess;

    public function index(Request $request)
    {
        $this->authorizeInventory('riwayat-mutasi.index');
        $request->validate(['id_barang' => 'nullable|integer|exists:barang,id', 'jenis' => 'nullable|in:MASUK,KELUAR']);
        if ($request->ajax()) {
            $rows = collect();
            if ($request->jenis !== 'KELUAR') {
                $incoming = DB::table('barang_masuk_detail as detail')
                    ->join('barang_masuk as receipt', 'detail.id_masuk', '=', 'receipt.id')
                    ->join('barang', 'detail.id_barang', '=', 'barang.id')
                    ->leftJoin('supplier', 'receipt.id_supplier', '=', 'supplier.id')
                    ->when($request->filled('id_barang'), fn ($query) => $query->where('detail.id_barang', $request->id_barang))
                    ->select('receipt.tanggal', 'detail.id_barang', 'detail.jumlah as qty', 'barang.nama as nama_barang', 'supplier.nama as supplier', 'receipt.id as ref_id')->get()
                    ->map(function ($row) { $row->jenis = 'MASUK'; $row->ref = $row->supplier ? 'Supplier: '.$row->supplier : 'Barang Masuk #'.$row->ref_id; return $row; });
                $rows = $rows->concat($incoming);
            }
            if ($request->jenis !== 'MASUK') {
                $outgoing = DB::table('barang_keluar_detail as detail')
                    ->join('barang_keluar as issue', 'detail.id_barang_keluar', '=', 'issue.id')
                    ->join('barang', 'detail.id_barang', '=', 'barang.id')
                    ->leftJoin('kavling_peta as unit', 'issue.id_kavling', '=', 'unit.id')
                    ->leftJoin('lokasi_kavling as lokasi', 'issue.id_lokasi', '=', 'lokasi.id')
                    ->when($request->filled('id_barang'), fn ($query) => $query->where('detail.id_barang', $request->id_barang))
                    ->select('issue.tanggal', 'detail.id_barang', 'detail.jumlah as qty', 'barang.nama as nama_barang', 'unit.kode_kavling', 'lokasi.nama_kavling', 'issue.id as ref_id')->get()
                    ->map(function ($row) { $row->jenis = 'KELUAR'; $row->ref = $row->kode_kavling ? 'Unit: '.$row->kode_kavling : ($row->nama_kavling ? 'Perumahan: '.$row->nama_kavling : 'Barang Keluar #'.$row->ref_id); return $row; });
                $rows = $rows->concat($outgoing);
            }
            return DataTables::of($rows->sortByDesc('tanggal')->values())->addIndexColumn()
                ->editColumn('jenis', fn ($row) => '<span class="badge badge-'.($row->jenis === 'MASUK' ? 'success' : 'danger').'">'.$row->jenis.'</span>')
                ->rawColumns(['jenis'])->make(true);
        }
        $barangList = Barang::orderBy('nama')->get();
        return view('admin.gudang.riwayat_mutasi.index', compact('barangList'));
    }
}
