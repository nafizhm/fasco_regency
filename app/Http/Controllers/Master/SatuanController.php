<?php

namespace App\Http\Controllers\Master;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\Satuan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;


class SatuanController extends Controller
{
    use \App\Traits\InventoryAccess;
    public function index(Request $request)
    {
        $this->authorizeInventory('satuan.index', 'lihat');
        $permissions = HakAksesController::getUserPermissions();

        if ($request->ajax()) {
            $data = Satuan::orderBy('id', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) use ($permissions) {
                    $editUrl = route('satuan.edit', $row->id);
                    $deleteUrl = route('satuan.destroy', $row->id);

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

                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.master.satuan.index', compact('permissions'));
    }

    public function edit($id)
    {
        $this->authorizeInventory('satuan.index', 'edit');
        $list = Satuan::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $list,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeInventory('satuan.index', 'tambah');
        $rules = [
            'nama_satuan' => 'required|string|max:255|unique:satuan,nama_satuan',
        ];

        $messages = [
            'nama_satuan.required' => 'Nama Satuan wajib diisi.',
            'nama_satuan.unique' => 'Nama Satuan sudah digunakan.',
        ];

        $request->validate($rules, $messages);

        DB::beginTransaction();
        try {
            $db = [
                'nama_satuan' => $request->nama_satuan,
            ];

            Satuan::create($db);

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SatuanController@store Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat menyimpan data.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $this->authorizeInventory('satuan.index', 'edit');
        $data = Satuan::findOrFail($id);

        $rules = [
            'nama_satuan' => 'required|string|max:255|unique:satuan,nama_satuan,' . $data->id . ',id',
        ];

        $messages = [
            'nama_satuan.required' => 'Nama Satuan wajib diisi.',
            'nama_satuan.unique' => 'Nama Satuan sudah digunakan.',
        ];

        $request->validate($rules, $messages);

        DB::beginTransaction();
        try {
            $db = [
                'nama_satuan' => $request->nama_satuan,
            ];

            $data->update($db);

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SatuanController@update Error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat memperbarui data.'], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorizeInventory('satuan.index', 'hapus');
        DB::beginTransaction();
        try {
            $data = Satuan::whereKey($id)->lockForUpdate()->firstOrFail();
            if (DB::table('barang')->where('id_satuan', $id)->exists()) {
                DB::rollBack();
                return response()->json(['message' => 'Data sudah digunakan dan tidak dapat dihapus.'], 422);
            }

            $data->delete();

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SatuanController@destroy Error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat menghapus data.'], 500);
        }
    }
}
