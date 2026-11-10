<?php

namespace App\Http\Controllers\Pengaturan;

use App\Http\Controllers\Controller;
use App\Models\KavlingPeta;
use App\Models\LokasiKavling;
use App\Models\MasterProject;
use App\Models\SettingProyek;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class SettingProyekController extends Controller
{
    use \App\Traits\InventoryAccess;

    public function index(Request $request)
    {
        $this->authorizeInventory('setting-proyek.index', 'lihat');
        $permissions = HakAksesController::getUserPermissions();

        if ($request->ajax()) {
            $data = SettingProyek::with(['lokasi', 'kavling', 'masterProject'])
                ->join('lokasi_kavling', 'setting_proyek.id_lokasi', '=', 'lokasi_kavling.id')
                ->join('kavling_peta', 'setting_proyek.id_kavling', '=', 'kavling_peta.id')
                ->orderBy('lokasi_kavling.urutan')
                ->orderBy('kavling_peta.kode_kavling')
                ->select('setting_proyek.*');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('lokasi_kavling', function ($row) {
                    return $row->lokasi?->nama_kavling ?? '-';
                })
                ->addColumn('lokasi_unit', function ($row) {
                    return $row->kavling?->kode_kavling ?? '-';
                })
                ->addColumn('type_rumah', function ($row) {
                    return $row->masterProject?->jenis_proyek ?? '-';
                })
                ->addColumn('status_label', function ($row) {
                    return $this->getStatusLabel((int) $row->status);
                })
                ->addColumn('action', function ($row) use ($permissions) {
                    $editUrl = route('setting-proyek.edit', $row->id);
                    $deleteUrl = route('setting-proyek.destroy', $row->id);

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
                ->filterColumn('lokasi_unit', function ($query, $keyword) {
                    $query->whereHas('kavling', function ($q) use ($keyword) {
                        $q->where('kode_kavling', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $lokasiList = LokasiKavling::orderBy('nama_kavling')->get();
        $masterProjectList = MasterProject::where('jenis_pekerjaan', 'pembangunan_unit')->orderBy('jenis_proyek')->get();

        return view('admin.pengaturan.setting_proyek.index', compact(
            'permissions',
            'lokasiList',
            'masterProjectList'
        ));
    }

    public function getUnitsByLokasi($id)
    {
        $this->authorizeInventory('setting-proyek.index', 'lihat');
        $units = KavlingPeta::where('id_lokasi', $id)
            ->orderBy('kode_kavling')
            ->get(['id as id_kavling', 'kode_kavling']);

        return response()->json($units);
    }

    public function edit($id)
    {
        $this->authorizeInventory('setting-proyek.index', 'edit');
        $data = SettingProyek::findOrFail($id);
        $unitList = KavlingPeta::where('id_lokasi', $data->id_lokasi)
            ->orderBy('kode_kavling')
            ->get(['id as id_kavling', 'kode_kavling']);

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'unit_list' => $unitList,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeInventory('setting-proyek.index', 'tambah');
        $request->validate($this->rules(), $this->messages());

        DB::beginTransaction();
        try {
            $this->ensureUnitBelongsToLokasi((int) $request->id_lokasi, (int) $request->id_kavling);

            SettingProyek::create([
                'id_lokasi' => $request->id_lokasi,
                'id_kavling' => $request->id_kavling,
                'id_master_project' => $request->id_master_project,
                'id_tukang' => null,
                'status' => $request->status_proyek,
            ]);

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SettingProyekController@store Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat menyimpan data.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $this->authorizeInventory('setting-proyek.index', 'edit');
        $data = SettingProyek::findOrFail($id);

        $request->validate($this->rules($id), $this->messages());
        if ((int) $data->id_kavling !== (int) $request->id_kavling || (int) $data->id_master_project !== (int) $request->id_master_project) {
            if (\App\Models\BarangKeluar::where('id_kavling', $data->id_kavling)->exists()) {
                throw ValidationException::withMessages(['id_master_project' => 'Unit dan master project yang sudah dipakai oleh barang keluar tidak dapat diganti.']);
            }
        }

        DB::beginTransaction();
        try {
            $this->ensureUnitBelongsToLokasi((int) $request->id_lokasi, (int) $request->id_kavling);

            $data->update([
                'id_lokasi' => $request->id_lokasi,
                'id_kavling' => $request->id_kavling,
                'id_master_project' => $request->id_master_project,
                'id_tukang' => null,
                'status' => $request->status_proyek,
            ]);

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SettingProyekController@update Error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat memperbarui data.'], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorizeInventory('setting-proyek.index', 'hapus');
        $setting = SettingProyek::findOrFail($id);
        if (\App\Models\BarangKeluar::where('id_kavling', $setting->id_kavling)->exists()) {
            throw ValidationException::withMessages(['setting_proyek' => 'Setting proyek masih dipakai oleh barang keluar.']);
        }
        DB::beginTransaction();
        try {
            $data = SettingProyek::findOrFail($id);
            $data->delete();

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SettingProyekController@destroy Error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat menghapus data.'], 500);
        }
    }

    private function rules(?int $id = null): array
    {
        $uniqueKavling = 'required|exists:kavling_peta,id|unique:setting_proyek,id_kavling';
        if ($id) {
            $uniqueKavling .= ',' . $id . ',id';
        }

        return [
            'id_lokasi' => 'required|exists:lokasi_kavling,id',
            'id_kavling' => $uniqueKavling,
            'id_master_project' => ['required', \Illuminate\Validation\Rule::exists('master_project', 'id')->where('jenis_pekerjaan', 'pembangunan_unit')],
            'status_proyek' => 'required|in:1,2,3',
        ];
    }

    private function messages(): array
    {
        return [
            'id_lokasi.required' => 'Lokasi kavling wajib dipilih.',
            'id_kavling.required' => 'Lokasi unit wajib dipilih.',
            'id_kavling.unique' => 'Lokasi unit sudah pernah disetting.',
            'id_master_project.required' => 'Type rumah wajib dipilih.',
            'status_proyek.required' => 'Status proyek wajib dipilih.',
        ];
    }

    private function getStatusLabel(int $status): string
    {
        return match ($status) {
            1 => 'Sudah Disetting',
            2 => 'Proses',
            3 => 'Selesai',
            default => 'Belum di Setting',
        };
    }

    private function ensureUnitBelongsToLokasi(int $idLokasi, int $idKavling): void
    {
        $unit = KavlingPeta::where('id', $idKavling)
            ->where('id_lokasi', $idLokasi)
            ->first();

        if (!$unit) {
            throw ValidationException::withMessages([
                'id_kavling' => 'Lokasi unit tidak sesuai dengan lokasi kavling yang dipilih.',
            ]);
        }
    }
}
