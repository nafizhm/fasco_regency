<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\Barang;
use App\Models\PengajuanBarang;
use App\Models\PengajuanBarangDetail;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class PengajuanBarangController extends Controller
{
    use \App\Traits\InventoryAccess;

    public function index(Request $request)
    {
        $this->authorizeInventory('pengajuan-barang.index', 'lihat');
        Carbon::setLocale('id');
        $permissions = HakAksesController::getUserPermissions();

        if ($request->ajax()) {
            $data = PengajuanBarang::with('supplier')->orderBy('id', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('tanggal', function ($row) {
                    return Carbon::parse($row->tanggal)->translatedFormat('d F Y');
                })
                ->addColumn('supplier', function ($row) {
                    return $row->supplier?->nama ?? '-';
                })
                ->addColumn('jum_item', function ($row) {
                    return number_format((int) $row->jum_item, 0, ',', '.');
                })
                ->addColumn('total_harga', function ($row) {
                    return '<div class="d-flex justify-content-between"><span>Rp.</span><span>' . number_format((int) $row->total_harga, 0, ',', '.') . '</span></div>';
                })
                ->addColumn('status_label', function ($row) {
                    if ((int) $row->status === 1) {
                        return '<span class="badge badge-primary">Disetujui</span>';
                    }

                    return '<span class="badge badge-warning">Pengajuan</span>';
                })
                ->addColumn('action', function ($row) use ($permissions) {
                    $showUrl = route('pengajuan-barang.show', $row->id);
                    $deleteUrl = route('pengajuan-barang.destroy', $row->id);

                    $btn = '<div class="d-flex justify-content-center">';
                    if ($permissions['edit']) {
                        $btn .= '<a href="' . e($showUrl) . '" class="btn btn-primary btn-sm mx-1">Edit</a>';
                    }
                    if ($permissions['hapus']) {
                        $btn .= '<form action="' . e($deleteUrl) . '" method="POST" style="display:inline;">'
                            . csrf_field()
                            . method_field('DELETE')
                            . '<button type="submit" class="delete-button btn btn-danger btn-sm mx-1">Hapus</button></form>';
                    }
                    $btn .= '</div>';

                    return $btn;
                })
                ->rawColumns(['total_harga', 'status_label', 'action'])
                ->make(true);
        }

        return view('admin.gudang.pengajuan_barang.index', compact('permissions'));
    }

    public function create()
    {
        $this->authorizeInventory('pengajuan-barang.index', 'tambah');
        $supplierList = Supplier::orderBy('nama')->get();
        $barangList = Barang::with('satuan')->orderBy('nama')->get();

        return view('admin.gudang.pengajuan_barang.create', compact('supplierList', 'barangList'));
    }

    public function show($id)
    {
        $this->authorizeInventory('pengajuan-barang.index', 'lihat');
        $data = PengajuanBarang::findOrFail($id);
        $detailList = PengajuanBarangDetail::where('id_pengajuan', $data->id)->orderBy('id')->get();
        $supplierList = Supplier::orderBy('nama')->get();
        $barangList = Barang::with('satuan')->orderBy('nama')->get();

        return view('admin.gudang.pengajuan_barang.show', compact(
            'data',
            'detailList',
            'supplierList',
            'barangList'
        ));
    }

    public function edit($id)
    {
        $this->authorizeInventory('pengajuan-barang.index', 'edit');
        return redirect()->route('pengajuan-barang.show', $id);
    }

    public function store(Request $request)
    {
        $this->authorizeInventory('pengajuan-barang.index', 'tambah');
        $this->normalizeAmounts($request);
        $request->validate($this->rules(), $this->messages());

        DB::beginTransaction();
        try {
            $summary = $this->buildSummary($request);

            $data = PengajuanBarang::create([
                'tanggal' => $request->tanggal,
                'id_supplier' => $request->id_supplier,
                'total_harga' => $summary['total_harga'],
                'jum_item' => $summary['jum_item'],
                'status' => (int) $request->status,
                'keterangan' => $request->keterangan_pengajuan ?? '',
            ]);

            $this->syncDetail($data, $request);

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PengajuanBarangController@store Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan Pengajuan Barang.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $this->authorizeInventory('pengajuan-barang.index', 'edit');
        $data = PengajuanBarang::findOrFail($id);

        $this->normalizeAmounts($request);
        $request->validate($this->rules(), $this->messages());

        DB::beginTransaction();
        try {
            $summary = $this->buildSummary($request);

            $data->update([
                'tanggal' => $request->tanggal,
                'id_supplier' => $request->id_supplier,
                'total_harga' => $summary['total_harga'],
                'jum_item' => $summary['jum_item'],
                'status' => (int) $request->status,
                'keterangan' => $request->keterangan_pengajuan ?? '',
            ]);

            PengajuanBarangDetail::where('id_pengajuan', $data->id)->delete();
            $this->syncDetail($data, $request);

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PengajuanBarangController@update Error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Gagal memperbarui Pengajuan Barang.'], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorizeInventory('pengajuan-barang.index', 'hapus');
        DB::beginTransaction();
        try {
            $data = PengajuanBarang::findOrFail($id);
            PengajuanBarangDetail::where('id_pengajuan', $data->id)->delete();
            $data->delete();

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PengajuanBarangController@destroy Error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Gagal menghapus Pengajuan Barang.'], 500);
        }
    }

    private function rules(): array
    {
        return [
            'tanggal' => 'required|date',
            'id_supplier' => 'required|exists:supplier,id',
            'status' => 'required|in:0,1',
            'id_barang' => 'required|array|min:1',
            'id_barang.*' => 'required|distinct|exists:barang,id',
            'jumlah' => 'required|array|min:1',
            'jumlah.*' => 'required|integer|min:1',
            'harga_beli' => 'required|array|min:1',
            'harga_beli.*' => 'required|integer|min:0',
            'keterangan_pengajuan' => 'nullable|string',
            'keterangan_detail' => 'nullable|array',
            'keterangan_detail.*' => 'nullable',
        ];
    }

    private function messages(): array
    {
        return [
            'tanggal.required' => 'Tanggal wajib diisi.',
            'id_supplier.required' => 'Supplier wajib dipilih.',
            'id_supplier.exists' => 'Supplier tidak ditemukan.',
            'status.required' => 'Status wajib dipilih.',
            'id_barang.required' => 'Minimal 1 barang harus diisi.',
            'id_barang.*.required' => 'Barang harus dipilih untuk setiap baris.',
            'id_barang.*.distinct' => 'Barang tidak boleh dipilih lebih dari satu kali.',
            'id_barang.*.exists' => 'Barang yang dipilih tidak valid.',
            'jumlah.required' => 'Jumlah harus diisi.',
            'jumlah.*.required' => 'Jumlah harus diisi untuk setiap barang.',
            'harga_beli.required' => 'Harga beli harus diisi.',
            'harga_beli.*.required' => 'Harga beli harus diisi untuk setiap barang.',
        ];
    }

    private function syncDetail(PengajuanBarang $data, Request $request): void
    {
        foreach ($request->id_barang as $index => $idBarang) {
            $jumlah = (int) str_replace('.', '', $request->jumlah[$index]);
            $hargaBeli = (int) str_replace('.', '', $request->harga_beli[$index]);

            PengajuanBarangDetail::create([
                'id_pengajuan' => $data->id,
                'id_barang' => $idBarang,
                'jumlah' => $jumlah,
                'harga_beli' => $hargaBeli,
                'sub_total' => $jumlah * $hargaBeli,
                'keterangan' => $request->keterangan_detail[$index] ?? '',
            ]);
        }
    }

    private function buildSummary(Request $request): array
    {
        $totalHarga = 0;

        foreach ($request->id_barang as $index => $idBarang) {
            $jumlah = (int) str_replace('.', '', $request->jumlah[$index]);
            $hargaBeli = (int) str_replace('.', '', $request->harga_beli[$index]);
            $totalHarga += ($jumlah * $hargaBeli);
        }

        return [
            'total_harga' => $totalHarga,
            'jum_item' => count($request->id_barang),
        ];
    }

    private function normalizeAmounts(Request $request): void
    {
        foreach (['jumlah', 'harga_beli'] as $field) {
            $values = $request->input($field);
            if (!is_array($values)) continue;
            $request->merge([$field => array_map(function ($value) {
                if (is_scalar($value) && preg_match('/^\d{1,3}(?:\.\d{3})+$/', (string) $value)) {
                    return str_replace('.', '', (string) $value);
                }
                return $value;
            }, $values)]);
        }
        $count = is_array($request->input('id_barang')) ? count($request->input('id_barang')) : 0;
        foreach (['jumlah', 'harga_beli', 'id_master_project_pekerjaan'] as $field) {
            if ($request->has($field) && (!is_array($request->input($field)) || count($request->input($field)) !== $count)) {
                throw \Illuminate\Validation\ValidationException::withMessages([$field => 'Setiap barang harus memiliki isian yang lengkap.']);
            }
        }
    }
}
