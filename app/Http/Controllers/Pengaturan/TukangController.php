<?php

namespace App\Http\Controllers\Pengaturan;

use App\Http\Controllers\Controller;
use App\Models\Tukang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class TukangController extends Controller
{
    use \App\Traits\InventoryAccess;

    public function index(Request $request)
    {
        $this->authorizeInventory('tukang.index', 'lihat');
        $permissions = HakAksesController::getUserPermissions();

        if ($request->ajax()) {
            $data = Tukang::orderBy('id', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) use ($permissions) {
                    $editUrl = route('tukang.edit', $row->id);
                    $deleteUrl = route('tukang.destroy', $row->id);

                    $btn = '<div class="d-flex justify-content-center">';
                    if ($permissions['edit']) {
                        $btn .= '<button class="btn btn-primary btn-sm mx-1 edit-button" data-id="' . e($row->id) . '" data-url="' . e($editUrl) . '">Edit</button>';
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
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.pengaturan.tukang.index', compact('permissions'));
    }

    public function edit($id)
    {
        $this->authorizeInventory('tukang.index', 'edit');
        $list = Tukang::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $list,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeInventory('tukang.index', 'tambah');
        $rules = [
            'nama' => 'required|unique:tukang,nama',
            'alamat' => 'nullable',
            'no_telp' => 'nullable',
            'gaji_per_hari' => 'required|integer|min:0|max:1000000000',
        ];

        $messages = [
            'nama.required' => 'Nama tukang wajib diisi.',
            'nama.unique' => 'Nama tukang sudah digunakan.',
        ];

        $request->validate($rules, $messages);

        DB::beginTransaction();
        try {
            Tukang::create([
                'nama' => $request->nama,
                'alamat' => $request->alamat,
                'no_telp' => $request->no_telp,
                'gaji_per_hari' => $request->gaji_per_hari,
            ]);

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TukangController@store Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat menyimpan data.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $this->authorizeInventory('tukang.index', 'edit');
        $data = Tukang::findOrFail($id);

        $rules = [
            'nama' => 'required|unique:tukang,nama,' . $id . ',id',
            'alamat' => 'nullable',
            'no_telp' => 'nullable',
            'gaji_per_hari' => 'required|integer|min:0|max:1000000000',
        ];

        $messages = [
            'nama.required' => 'Nama tukang wajib diisi.',
            'nama.unique' => 'Nama tukang sudah digunakan.',
        ];

        $request->validate($rules, $messages);

        DB::beginTransaction();
        try {
            $data->update([
                'nama' => $request->nama,
                'alamat' => $request->input('alamat', $data->alamat),
                'no_telp' => $request->no_telp,
                'gaji_per_hari' => $request->gaji_per_hari,
            ]);

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TukangController@update Error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat memperbarui data.'], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorizeInventory('tukang.index', 'hapus');
        if (DB::table('absen_tukang_detail')->where('id_tukang', $id)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['tukang' => 'Tukang sudah memiliki riwayat absensi dan tidak dapat dihapus.']);
        }
        if (\App\Models\BarangKeluar::where('id_tukang', $id)->exists() || \App\Models\SettingProyek::where('id_tukang', $id)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['tukang' => 'Tukang masih dipakai oleh transaksi atau setting proyek.']);
        }
        DB::beginTransaction();
        try {
            $data = Tukang::findOrFail($id);
            $data->delete();

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TukangController@destroy Error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat menghapus data.'], 500);
        }
    }
}
