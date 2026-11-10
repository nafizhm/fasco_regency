<?php

namespace App\Http\Controllers\Pengaturan;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\MasterProject;
use App\Models\MasterProjectDetail;
use App\Models\MasterProjectPekerjaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class MasterProjectController extends Controller
{
    use \App\Traits\InventoryAccess;

    public function index(Request $request)
    {
        $this->authorizeInventory('master-project.index', 'lihat');
        $access = DB::table('hak_akses')->join('menu', 'menu.id', '=', 'hak_akses.id_menu')
            ->where('hak_akses.id_user', auth()->id())->where('menu.route_name', 'master-project.index')
            ->select('hak_akses.tambah', 'hak_akses.edit', 'hak_akses.hapus')->first();
        $permissions = ['tambah' => $access?->tambah ?? 0, 'edit' => $access?->edit ?? 0, 'hapus' => $access?->hapus ?? 0];

        if ($request->ajax()) {
            $data = MasterProject::with('details')->orderBy('id', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('jenis_pekerjaan_label', function ($row) {
                    return $this->getJenisPekerjaanLabel($row->jenis_pekerjaan);
                })
                ->addColumn('jumlah_material', function ($row) {
                    $total = (int) $row->details->where('tipe', 'material')->sum('sub_total');
                    return 'Rp ' . number_format($total, 0, ',', '.');
                })
                ->addColumn('jumlah_jasa', function ($row) {
                    $total = (int) $row->details->where('tipe', 'jasa')->sum('sub_total');
                    return 'Rp ' . number_format($total, 0, ',', '.');
                })
                ->addColumn('action', function ($row) use ($permissions) {
                    $editUrl = route('master-project.edit', $row->id);
                    $pekerjaanUrl = route('master-project.pekerjaan.index', $row->id);
                    $deleteUrl = route('master-project.destroy', $row->id);

                    $btn = '<div class="d-flex justify-content-center">';

                    if ($permissions['edit']) {
                        $btn .= '<button type="button" class="btn btn-info btn-sm mx-1 edit-button" data-id="' . e($row->id) . '" data-url="' . e($editUrl) . '">Edit</button>';
                        $btn .= '<a href="' . e($pekerjaanUrl) . '" class="btn btn-primary btn-sm mx-1">Jenis Pekerjaan</a>';
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

        return view('admin.pengaturan.master_project.index', compact('permissions'));
    }

    public function store(Request $request)
    {
        $this->authorizeInventory('master-project.index', 'tambah');
        $request->validate([
            'jenis_proyek' => 'required|string|max:255|unique:master_project,jenis_proyek',
            'jenis_pekerjaan' => 'required|in:pembangunan_unit,non_unit',
        ], [
            'jenis_proyek.required' => 'Jenis proyek wajib diisi.',
            'jenis_proyek.unique' => 'Jenis proyek sudah digunakan.',
            'jenis_pekerjaan.required' => 'Kategori project wajib dipilih.',
            'jenis_pekerjaan.in' => 'Kategori project tidak valid.',
        ]);

        DB::beginTransaction();
        try {
            MasterProject::create([
                'jenis_proyek' => $request->jenis_proyek,
                'jenis_pekerjaan' => $request->jenis_pekerjaan,
            ]);

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MasterProjectController@store Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat menyimpan data.'], 500);
        }
    }

    public function edit($id)
    {
        $this->authorizeInventory('master-project.index', 'edit');
        $project = MasterProject::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $project,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeInventory('master-project.index', 'edit');
        $project = MasterProject::findOrFail($id);

        $request->validate([
            'jenis_proyek' => 'required|string|max:255|unique:master_project,jenis_proyek,' . $id . ',id',
            'jenis_pekerjaan' => 'required|in:pembangunan_unit,non_unit',
        ], [
            'jenis_proyek.required' => 'Jenis proyek wajib diisi.',
            'jenis_proyek.unique' => 'Jenis proyek sudah digunakan.',
            'jenis_pekerjaan.required' => 'Kategori project wajib dipilih.',
            'jenis_pekerjaan.in' => 'Kategori project tidak valid.',
        ]);

        if ($project->jenis_pekerjaan !== $request->jenis_pekerjaan
            && (\App\Models\SettingProyek::where('id_master_project', $id)->exists()
                || \App\Models\BarangKeluar::where('id_project', $id)->exists())) {
            throw \Illuminate\Validation\ValidationException::withMessages(['jenis_pekerjaan' => 'Kategori project yang sudah digunakan tidak dapat diubah.']);
        }
        DB::beginTransaction();
        try {
            $project->update([
                'jenis_proyek' => $request->jenis_proyek,
                'jenis_pekerjaan' => $request->jenis_pekerjaan,
            ]);

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MasterProjectController@update Error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat memperbarui data.'], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorizeInventory('master-project.index', 'hapus');
        if (\App\Models\SettingProyek::where('id_master_project', $id)->exists()
            || \App\Models\BarangKeluar::where('id_project', $id)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['project' => 'Master project masih dipakai oleh setting proyek atau barang keluar.']);
        }
        DB::beginTransaction();
        try {
            $project = MasterProject::findOrFail($id);
            $project->details()->delete();
            $project->pekerjaan()->delete();
            $project->delete();

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MasterProjectController@destroy Error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat menghapus data.'], 500);
        }
    }

    public function material($id)
    {
        $this->authorizeInventory('master-project.index', 'lihat');
        $access = DB::table('hak_akses')->join('menu', 'menu.id', '=', 'hak_akses.id_menu')
            ->where('hak_akses.id_user', auth()->id())->where('menu.route_name', 'master-project.index')
            ->select('hak_akses.tambah', 'hak_akses.edit', 'hak_akses.hapus')->first();
        $permissions = ['tambah' => $access?->tambah ?? 0, 'edit' => $access?->edit ?? 0, 'hapus' => $access?->hapus ?? 0];
        $pekerjaan = MasterProjectPekerjaan::with('project')->findOrFail($id);
        $barangList = Barang::with('satuan')->orderBy('nama')->get();
        $details = MasterProjectDetail::where('id_master_project_pekerjaan', $pekerjaan->id)
            ->where('tipe', 'material')
            ->orderBy('id')
            ->get();

        return view('admin.pengaturan.master_project.material', compact('permissions', 'pekerjaan', 'barangList', 'details'));
    }

    public function jasa($id)
    {
        $this->authorizeInventory('master-project.index', 'lihat');
        $access = DB::table('hak_akses')->join('menu', 'menu.id', '=', 'hak_akses.id_menu')
            ->where('hak_akses.id_user', auth()->id())->where('menu.route_name', 'master-project.index')
            ->select('hak_akses.tambah', 'hak_akses.edit', 'hak_akses.hapus')->first();
        $permissions = ['tambah' => $access?->tambah ?? 0, 'edit' => $access?->edit ?? 0, 'hapus' => $access?->hapus ?? 0];
        $pekerjaan = MasterProjectPekerjaan::with('project')->findOrFail($id);
        $details = MasterProjectDetail::where('id_master_project_pekerjaan', $pekerjaan->id)
            ->where('tipe', 'jasa')
            ->orderBy('id')
            ->get();

        return view('admin.pengaturan.master_project.jasa', compact('permissions', 'pekerjaan', 'details'));
    }

    public function updateMaterial(Request $request, $id)
    {
        $this->authorizeInventory('master-project.index', 'edit');
        $pekerjaan = MasterProjectPekerjaan::with('project')->findOrFail($id);

        $request->validate([
            'id_barang' => 'required|array|min:1',
            'id_barang.*' => 'required|exists:barang,id',
            'qty' => 'required|array|min:1',
            'qty.*' => ['required', 'regex:/^\d+(?:\.\d{3})*(?:,\d{1,2})?$/', function ($attribute, $value, $fail) { if ($this->parseDecimal((string) $value) <= 0) $fail('Qty harus lebih besar dari 0.'); }],
            'harga_satuan' => 'required|array|min:1',
            'harga_satuan.*' => ['required', 'regex:/^\d+(?:\.\d{3})*$/'],
        ], [
            'id_barang.required' => 'Minimal 1 material wajib diisi.',
            'id_barang.*.required' => 'Material wajib dipilih.',
            'qty.required' => 'Qty wajib diisi.',
            'qty.*.required' => 'Qty wajib diisi.',
            'harga_satuan.required' => 'Harga satuan wajib diisi.',
            'harga_satuan.*.required' => 'Harga satuan wajib diisi.',
        ]);

        foreach (['qty', 'harga_satuan'] as $field) {
            if (count($request->input($field)) !== count($request->input('id_barang'))) {
                throw \Illuminate\Validation\ValidationException::withMessages([$field => 'Isian setiap baris harus lengkap.']);
            }
        }
        DB::beginTransaction();
        try {
            MasterProjectDetail::where('id_master_project_pekerjaan', $pekerjaan->id)
                ->where('tipe', 'material')
                ->delete();

            foreach ($request->id_barang as $index => $idBarang) {
                $barang = Barang::with('satuan')->findOrFail($idBarang);
                $qty = $this->parseDecimal($request->qty[$index]);
                $hargaSatuan = $this->parseNumber($request->harga_satuan[$index]);

                MasterProjectDetail::create([
                    'id_master_project' => $pekerjaan->id_master_project,
                    'id_master_project_pekerjaan' => $pekerjaan->id,
                    'tipe' => 'material',
                    'id_barang' => $barang->id,
                    'nama_item' => $barang->nama,
                    'satuan' => $barang->satuan?->nama_satuan ?? '',
                    'qty' => $qty,
                    'harga_satuan' => $hargaSatuan,
                    'sub_total' => (int) round($qty * $hargaSatuan),
                ]);
            }

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MasterProjectController@updateMaterial Error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat menyimpan material.'], 500);
        }
    }

    public function updateJasa(Request $request, $id)
    {
        $this->authorizeInventory('master-project.index', 'edit');
        $pekerjaan = MasterProjectPekerjaan::with('project')->findOrFail($id);

        $request->validate([
            'nama_item' => 'required|array|min:1',
            'nama_item.*' => 'required|string|max:255',
            'satuan' => 'nullable|array',
            'satuan.*' => 'nullable|string|max:100',
            'qty' => 'required|array|min:1',
            'qty.*' => ['required', 'regex:/^\d+(?:\.\d{3})*(?:,\d{1,2})?$/', function ($attribute, $value, $fail) { if ($this->parseDecimal((string) $value) <= 0) $fail('Qty harus lebih besar dari 0.'); }],
            'harga_satuan' => 'required|array|min:1',
            'harga_satuan.*' => ['required', 'regex:/^\d+(?:\.\d{3})*$/'],
        ], [
            'nama_item.required' => 'Minimal 1 jasa wajib diisi.',
            'nama_item.*.required' => 'Nama jasa wajib diisi.',
            'qty.required' => 'Qty wajib diisi.',
            'qty.*.required' => 'Qty wajib diisi.',
            'harga_satuan.required' => 'Harga satuan wajib diisi.',
            'harga_satuan.*.required' => 'Harga satuan wajib diisi.',
        ]);

        foreach (['qty', 'harga_satuan'] as $field) {
            if (count($request->input($field)) !== count($request->input('nama_item'))) {
                throw \Illuminate\Validation\ValidationException::withMessages([$field => 'Isian setiap baris harus lengkap.']);
            }
        }
        DB::beginTransaction();
        try {
            MasterProjectDetail::where('id_master_project_pekerjaan', $pekerjaan->id)
                ->where('tipe', 'jasa')
                ->delete();

            foreach ($request->nama_item as $index => $namaItem) {
                $qty = $this->parseDecimal($request->qty[$index]);
                $hargaSatuan = $this->parseNumber($request->harga_satuan[$index]);

                MasterProjectDetail::create([
                    'id_master_project' => $pekerjaan->id_master_project,
                    'id_master_project_pekerjaan' => $pekerjaan->id,
                    'tipe' => 'jasa',
                    'id_barang' => null,
                    'nama_item' => $namaItem,
                    'satuan' => $request->satuan[$index] ?? '',
                    'qty' => $qty,
                    'harga_satuan' => $hargaSatuan,
                    'sub_total' => (int) round($qty * $hargaSatuan),
                ]);
            }

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MasterProjectController@updateJasa Error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat menyimpan jasa.'], 500);
        }
    }

    public function pekerjaanIndex($id)
    {
        $this->authorizeInventory('master-project.index', 'lihat');
        $access = DB::table('hak_akses')->join('menu', 'menu.id', '=', 'hak_akses.id_menu')
            ->where('hak_akses.id_user', auth()->id())->where('menu.route_name', 'master-project.index')
            ->select('hak_akses.tambah', 'hak_akses.edit', 'hak_akses.hapus')->first();
        $permissions = ['tambah' => $access?->tambah ?? 0, 'edit' => $access?->edit ?? 0, 'hapus' => $access?->hapus ?? 0];
        $project = MasterProject::findOrFail($id);
        $pekerjaanList = MasterProjectPekerjaan::with('details')
            ->where('id_master_project', $project->id)
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.pengaturan.master_project.pekerjaan', compact('permissions', 'project', 'pekerjaanList'));
    }

    public function storePekerjaan(Request $request, $id)
    {
        $this->authorizeInventory('master-project.index', 'tambah');
        $project = MasterProject::findOrFail($id);

        $request->validate([
            'nama_pekerjaan' => 'required|string|max:255',
        ], [
            'nama_pekerjaan.required' => 'Nama jenis pekerjaan wajib diisi.',
        ]);

        DB::beginTransaction();
        try {
            MasterProjectPekerjaan::create([
                'id_master_project' => $project->id,
                'nama_pekerjaan' => $request->nama_pekerjaan,
            ]);

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MasterProjectController@storePekerjaan Error: ' . $e->getMessage(), [
                'id_master_project' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat menyimpan jenis pekerjaan.'], 500);
        }
    }

    public function editPekerjaan($id)
    {
        $this->authorizeInventory('master-project.index', 'edit');
        $pekerjaan = MasterProjectPekerjaan::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $pekerjaan,
        ]);
    }

    public function updatePekerjaan(Request $request, $id)
    {
        $this->authorizeInventory('master-project.index', 'edit');
        $pekerjaan = MasterProjectPekerjaan::findOrFail($id);

        $request->validate([
            'nama_pekerjaan' => 'required|string|max:255',
        ], [
            'nama_pekerjaan.required' => 'Nama jenis pekerjaan wajib diisi.',
        ]);

        DB::beginTransaction();
        try {
            $pekerjaan->update([
                'nama_pekerjaan' => $request->nama_pekerjaan,
            ]);

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MasterProjectController@updatePekerjaan Error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat memperbarui jenis pekerjaan.'], 500);
        }
    }

    public function destroyPekerjaan($id)
    {
        $this->authorizeInventory('master-project.index', 'hapus');
        if (\App\Models\BarangKeluarDetail::where('id_master_project_pekerjaan', $id)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['pekerjaan' => 'Jenis pekerjaan masih dipakai oleh barang keluar.']);
        }
        DB::beginTransaction();
        try {
            $pekerjaan = MasterProjectPekerjaan::findOrFail($id);
            $pekerjaan->details()->delete();
            $pekerjaan->delete();

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MasterProjectController@destroyPekerjaan Error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan saat menghapus jenis pekerjaan.'], 500);
        }
    }

    private function parseNumber(?string $value): int
    {
        return (int) str_replace('.', '', (string) $value);
    }

    private function getJenisPekerjaanLabel(?string $jenisPekerjaan): string
    {
        return match ($jenisPekerjaan) {
            'non_unit' => 'Kategori Non Unit',
            default => 'Kategori Pembangunan Unit',
        };
    }

    private function parseDecimal(?string $value): float
    {
        $normalized = str_replace('.', '', (string) $value);
        $normalized = str_replace(',', '.', $normalized);

        return (float) $normalized;
    }
}
