<?php

namespace App\Http\Controllers\Master;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;


class SupplierController extends Controller
{
    use \App\Traits\InventoryAccess;
    public function index(Request $request)
    {
        $this->authorizeInventory('supplier.index', 'lihat');
        $permissions = HakAksesController::getUserPermissions();

        if ($request->ajax()) {
            $data = Supplier::orderBy('id', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()

                ->editColumn('status', function ($row) {
                    switch ($row->status) {
                        case 1:
                            return '<span class="badge bg-primary">Aktif</span>';
                        case 2:
                            return '<span class="badge bg-danger">Tidak Aktif</span>';
                        default:
                            return '<span class="badge bg-secondary">Tidak Diketahui</span>';
                    }
                })

                ->addColumn('action', function ($row) use ($permissions) {
                    $editUrl = route('supplier.edit', $row->id);
                    $deleteUrl = route('supplier.destroy', $row->id);

                    $btn = '<div class="d-flex justify-content-center">';
                    if ($permissions['edit']) {
                        $btn .= '<button class="btn btn-primary btn-sm mx-1 edit-button" 
                                data-id="' . e($row->id) . '" 
                                data-url="' . e($editUrl) . '">Edit</button>';
                    }

                    if ($permissions['hapus']) {
                        $btn .= '<form action="' . e($deleteUrl) . '" method="POST" style="display:inline;">
                    ' . csrf_field() . method_field('DELETE') . '
                    <button type="submit" class="delete-button btn btn-danger btn-sm mx-1">
                        Hapus
                    </button>
                    </form>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })

                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('admin.master.supplier.index', compact('permissions'));
    }

    public function edit($id)
    {
        $this->authorizeInventory('supplier.index', 'edit');
        $list = Supplier::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $list,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeInventory('supplier.index', 'tambah');
        $rules = [
            'nama' => 'required|string|max:255|unique:supplier,nama',
            'no_telp' => 'required|string|max:50|unique:supplier,no_telp',
            'alamat' => 'required|string|max:5000',
            'keterangan' => 'nullable|string|max:5000',
            'status' => 'required|in:1,2',
        ];

        $messages = [
            'nama.required' => 'Nama wajib diisi.',
            'nama.unique' => 'Nama sudah digunakan.',
            'no_telp.required' => 'No Telepon wajib diisi.',
            'no_telp.unique' => 'No Telepon sudah digunakan.',
            'alamat.required' => 'Alamat wajib diisi.',
            'keterangan.required' => 'Keterangan wajib diisi.',
            'status.required' => 'Status wajib diisi.',
            'status.in' => 'Status tidak valid.',
        ];

        $request->validate($rules, $messages);

        DB::beginTransaction();
        try {
            $latest = Supplier::where('kode', 'like', 'S-%')->orderBy('kode', 'desc')->first();

            if ($latest) {
                $lastNumber = (int) substr($latest->kode, 2);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            $kode = 'S-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);

            $db = [
                'kode' => $kode,
                'nama' => $request->nama,
                'no_telp' => $request->no_telp,
                'alamat' => $request->alamat,
                'keterangan' => $request->keterangan ?? '',
                'status' => $request->status,
            ];

            Supplier::create($db);

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SupplierController@store Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat menyimpan data.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $this->authorizeInventory('supplier.index', 'edit');
        $data = Supplier::findOrFail($id);

        $rules = [
            'nama' => 'required|string|max:255|unique:supplier,nama,' . $id . ',id',
            'no_telp' => 'required|string|max:50|unique:supplier,no_telp,' . $id . ',id',
            'alamat' => 'required|string|max:5000',
            'keterangan' => 'nullable|string|max:5000',
            'status' => 'required|in:1,2',
        ];

        $messages = [
            'nama.required' => 'Nama wajib diisi.',
            'nama.unique' => 'Nama sudah digunakan.',
            'no_telp.required' => 'No Telepon wajib diisi.',
            'no_telp.unique' => 'No Telepon sudah digunakan.',
            'alamat.required' => 'Alamat wajib diisi.',
            'keterangan.required' => 'Keterangan wajib diisi.',
            'status.required' => 'Status wajib diisi.',
            'status.in' => 'Status tidak valid.',
        ];

        $request->validate($rules, $messages);

        DB::beginTransaction();
        try {
            $db = [
                'nama' => $request->nama,
                'no_telp' => $request->no_telp,
                'alamat' => $request->alamat,
                'keterangan' => $request->keterangan ?? '',
                'status' => $request->status,
            ];

            $data->update($db);

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SupplierController@update Error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat memperbarui data.'], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorizeInventory('supplier.index', 'hapus');
        DB::beginTransaction();
        try {
            $data = Supplier::whereKey($id)->lockForUpdate()->firstOrFail();
            if (DB::table('barang_masuk')->where('id_supplier', $id)->exists()) {
                DB::rollBack();
                return response()->json(['message' => 'Data sudah digunakan dan tidak dapat dihapus.'], 422);
            }

            $data->delete();

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SupplierController@destroy Error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat menghapus data.'], 500);
        }
    }
}
