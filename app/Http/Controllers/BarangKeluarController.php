<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\BarangKeluarDetail;
use App\Models\KavlingPeta;
use App\Models\LokasiKavling;
use App\Models\MasterProject;
use App\Models\MasterProjectPekerjaan;
use App\Models\SettingProyek;
use App\Models\Tukang;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class BarangKeluarController extends Controller
{
    use \App\Traits\InventoryAccess;

    public function index(Request $request)
    {
        $this->authorizeInventory('barang-keluar.index', 'lihat');
        Carbon::setLocale('id');

        $permissions = HakAksesController::getUserPermissions();

        if ($request->ajax()) {
            $data = BarangKeluar::with(['lokasi', 'kavling', 'tukang', 'masterProject'])->orderBy('id', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('tanggal', function ($row) {
                    return Carbon::parse($row->tanggal)->translatedFormat('d F Y');
                })
                ->addColumn('lokasi', function ($row) {
                    return $row->lokasi?->nama_kavling ?? '-';
                })
                ->addColumn('unit', function ($row) {
                    return $row->kavling?->kode_kavling ?? $row->masterProject?->jenis_proyek ?? '-';
                })
                ->addColumn('jenis_pekerjaan', function ($row) {
                    return $row->id_project ? 'Non Unit' : 'Pembangunan Unit';
                })
                ->addColumn('tukang', function ($row) {
                    return $row->tukang?->nama ?? '-';
                })
                ->addColumn('jum_item', function ($row) {
                    return BarangKeluarDetail::where('id_barang_keluar', $row->id)->count();
                })
                ->addColumn('action', function ($row) use ($permissions) {
                    $showUrl = route('barang-keluar.show', $row->id);
                    $deleteUrl = route('barang-keluar.destroy', $row->id);

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
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.barang_keluar.index', compact('permissions'));
    }

    public function create()
    {
        $this->authorizeInventory('barang-keluar.index', 'tambah');
        $mode = request('mode') === 'non-unit' ? 'non-unit' : 'unit';
        $lokasiList = LokasiKavling::orderBy('nama_kavling')->get();
        $barangList = Barang::orderBy('nama')->get();
        $tukangList = Tukang::orderBy('nama')->get();
        $nonUnitProjectList = MasterProject::where('jenis_pekerjaan', 'non_unit')
            ->orderBy('jenis_proyek')
            ->get();

        return view('admin.barang_keluar.create', compact('mode', 'lokasiList', 'barangList', 'tukangList', 'nonUnitProjectList'));
    }

    public function nota(string $id)
    {
        $this->authorizeInventory('barang-keluar.index', 'lihat');
        Carbon::setLocale('id');

        $data = BarangKeluar::with(['detail.pekerjaan', 'detail.barang', 'lokasi', 'kavling', 'tukang', 'masterProject'])->findOrFail($id);
        $detailList = $data->detail;

        $company = app(\App\Services\InventoryCompany::class)->details($data->id_lokasi);

        $nota = [
            'no_nota' => 'BK-' . str_pad((string) $data->id, 5, '0', STR_PAD_LEFT),
            'tanggal' => Carbon::parse($data->tanggal)->translatedFormat('d F Y'),
            'jenis' => $data->id_project ? 'Non Unit' : 'Pembangunan Unit',
            'lokasi' => $data->lokasi?->nama_kavling ?? '-',
            'unit' => $data->kavling?->kode_kavling ?? $data->masterProject?->jenis_proyek ?? '-',
            'tukang' => $data->tukang?->nama ?? '-',
            'keterangan' => $data->keterangan ?: '-',
        ];

        return view('admin.barang_keluar.nota', compact('company', 'nota', 'detailList'));
    }

    public function getUnitsByLokasi($id)
    {
        $this->authorizeInventory('barang-keluar.index', 'lihat');
        $units = KavlingPeta::query()
            ->join('setting_proyek as sp', 'kavling_peta.id', '=', 'sp.id_kavling')
            ->where('kavling_peta.id_lokasi', $id)
            ->where('sp.id_lokasi', $id)
            ->orderBy('kode_kavling')
            ->get([
                'kavling_peta.id as id_kavling',
                'kavling_peta.kode_kavling',
                'sp.id_master_project',
            ]);

        return response()->json($units);
    }

    public function getPekerjaanByProject($id)
    {
        $this->authorizeInventory('barang-keluar.index', 'lihat');
        $pekerjaan = MasterProjectPekerjaan::query()
            ->where('id_master_project', $id)
            ->orderBy('id')
            ->get([
                'id',
                'nama_pekerjaan',
            ]);

        return response()->json($pekerjaan);
    }

    public function show($id)
    {
        $this->authorizeInventory('barang-keluar.index', 'lihat');
        $data = BarangKeluar::findOrFail($id);
        $mode = $data->id_project ? 'non-unit' : 'unit';
        $detailList = BarangKeluarDetail::with('pekerjaan')->where('id_barang_keluar', $data->id)->orderBy('id')->get();
        $lokasiList = LokasiKavling::orderBy('nama_kavling')->get();
        $barangList = Barang::orderBy('nama')->get();
        $tukangList = Tukang::orderBy('nama')->get();
        $nonUnitProjectList = MasterProject::where('jenis_pekerjaan', 'non_unit')
            ->orderBy('jenis_proyek')
            ->get();
        $unitList = collect();
        $pekerjaanList = collect();
        $selectedProjectId = null;

        if ($mode === 'unit' && $data->id_lokasi) {
            $unitList = KavlingPeta::query()
                ->join('setting_proyek as sp', 'kavling_peta.id', '=', 'sp.id_kavling')
                ->where('kavling_peta.id_lokasi', $data->id_lokasi)
                ->where('sp.id_lokasi', $data->id_lokasi)
                ->orderBy('kode_kavling')
                ->get([
                    'kavling_peta.id as id_kavling',
                    'kavling_peta.kode_kavling',
                    'sp.id_master_project',
                ]);

            if ($data->id_kavling && !$unitList->contains('id_kavling', $data->id_kavling)) {
                $currentUnit = KavlingPeta::query()
                    ->leftJoin('setting_proyek as sp', 'kavling_peta.id', '=', 'sp.id_kavling')
                    ->where('kavling_peta.id', $data->id_kavling)
                    ->first([
                        'kavling_peta.id as id_kavling',
                        'kavling_peta.kode_kavling',
                        'sp.id_master_project',
                    ]);

                if ($currentUnit) {
                    $unitList->push($currentUnit);
                }
            }

            $selectedProjectId = optional($unitList->firstWhere('id_kavling', $data->id_kavling))->id_master_project;
        } elseif ($data->id_project) {
            $selectedProjectId = $data->id_project;
        }

        if ($selectedProjectId) {
            $pekerjaanList = MasterProjectPekerjaan::where('id_master_project', $selectedProjectId)
                ->orderBy('id')
                ->get(['id', 'nama_pekerjaan']);
        }

        return view('admin.barang_keluar.show', compact(
            'data',
            'detailList',
            'lokasiList',
            'barangList',
            'tukangList',
            'unitList',
            'mode',
            'nonUnitProjectList',
            'pekerjaanList'
        ));
    }

    public function edit($id)
    {
        $this->authorizeInventory('barang-keluar.index', 'edit');
        return redirect()->route('barang-keluar.show', $id);
    }

    public function store(Request $request)
    {
        $this->authorizeInventory('barang-keluar.index', 'tambah');
        $this->normalizeAmounts($request);
        $request->validate($this->rules(), $this->messages());

        DB::beginTransaction();
        try {
            $payload = $this->resolveProjectPayload($request);
            $this->validateStokAvailability($request);

            $data = BarangKeluar::create([
                'tanggal' => $request->tanggal,
                'id_project' => $payload['id_project'],
                'id_lokasi' => $request->id_lokasi,
                'id_kavling' => $payload['id_kavling'],
                'id_tukang' => $request->id_tukang,
                'keterangan' => $request->keterangan ?? '',
                'lampiran' => '',
            ]);

            $this->syncDetail($data, $request);
            $data->load('detail');

            app(StockService::class)->addBarangKeluar($data);

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BarangKeluarController@store Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $this->authorizeInventory('barang-keluar.index', 'edit');
        $data = BarangKeluar::findOrFail($id);

        $this->normalizeAmounts($request);
        $request->validate($this->rules(), $this->messages());

        DB::beginTransaction();
        try {
            $data = BarangKeluar::whereKey($id)->lockForUpdate()->firstOrFail();
            $payload = $this->resolveProjectPayload($request);
            $this->validateStokAvailability($request, $data);

            $data->update([
                'tanggal' => $request->tanggal,
                'id_project' => $payload['id_project'],
                'id_lokasi' => $request->id_lokasi,
                'id_kavling' => $payload['id_kavling'],
                'id_tukang' => $request->id_tukang,
                'keterangan' => $request->keterangan ?? '',
                'lampiran' => $data->lampiran ?? '',
            ]);

            BarangKeluarDetail::where('id_barang_keluar', $data->id)->delete();
            $this->syncDetail($data, $request);
            $data->load('detail');

            app(StockService::class)->updateBarangKeluar($data);

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BarangKeluarController@update Error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorizeInventory('barang-keluar.index', 'hapus');
        $data = BarangKeluar::findOrFail($id);

        DB::beginTransaction();
        try {
            $data = BarangKeluar::whereKey($id)->lockForUpdate()->firstOrFail();
            Barang::whereIn('id', $data->detail()->pluck('id_barang'))->orderBy('id')->lockForUpdate()->get();
            app(StockService::class)->deleteBarangKeluar($data);

            BarangKeluarDetail::where('id_barang_keluar', $data->id)->delete();
            $data->delete();

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error destroying Barang Keluar: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal menghapus Barang Keluar.'], 500);
        }
    }

    private function rules(): array
    {
        $isNonUnit = request('mode') === 'non-unit';

        return [
            'mode' => 'required|in:unit,non-unit',
            'tanggal' => 'required|date',
            'id_lokasi' => 'required|exists:lokasi_kavling,id',
            'id_kavling' => $isNonUnit ? 'nullable' : 'required|exists:kavling_peta,id',
            'id_project' => $isNonUnit ? 'required|exists:master_project,id' : 'nullable',
            'id_tukang' => 'required|exists:tukang,id',
            'id_barang' => 'required|array|min:1',
            'id_barang.*' => 'required|distinct|exists:barang,id',
            'id_master_project_pekerjaan' => 'required|array|min:1',
            'id_master_project_pekerjaan.*' => 'required|exists:master_project_pekerjaan,id',
            'jumlah' => 'required|array|min:1',
            'jumlah.*' => 'required|integer|min:1',
            'keterangan_detail' => 'nullable|array',
            'keterangan_detail.*' => 'nullable',
            'keterangan' => 'nullable',
        ];
    }

    private function messages(): array
    {
        return [
            'tanggal.required' => 'Tanggal keluar wajib diisi.',
            'mode.required' => 'Tipe barang keluar wajib dipilih.',
            'id_lokasi.required' => 'Lokasi perumahan wajib dipilih.',
            'id_kavling.required' => 'Lokasi unit wajib dipilih.',
            'id_project.required' => 'Master proyek non unit wajib dipilih.',
            'id_tukang.required' => 'Nama tukang wajib dipilih.',
            'id_barang.required' => 'Minimal 1 barang harus diisi.',
            'id_barang.*.required' => 'Barang harus dipilih untuk setiap baris.',
            'id_barang.*.distinct' => 'Barang tidak boleh dipilih lebih dari satu kali.',
            'id_master_project_pekerjaan.required' => 'Jenis pekerjaan wajib dipilih untuk setiap barang.',
            'id_master_project_pekerjaan.*.required' => 'Jenis pekerjaan wajib dipilih untuk setiap barang.',
            'jumlah.required' => 'Jumlah harus diisi.',
            'jumlah.*.required' => 'Jumlah harus diisi untuk setiap barang.',
        ];
    }

    private function syncDetail(BarangKeluar $data, Request $request): void
    {
        $payload = $this->resolveProjectPayload($request);

        foreach ($request->id_barang as $index => $idBarang) {
            $jumlah = (int) str_replace('.', '', $request->jumlah[$index]);
            $idPekerjaan = $request->id_master_project_pekerjaan[$index] ?? null;

            $this->validatePekerjaanBelongsToProject($payload['project_source_id'], $idPekerjaan);

            BarangKeluarDetail::create([
                'id_barang_keluar' => $data->id,
                'id_barang' => $idBarang,
                'id_master_project_pekerjaan' => $idPekerjaan,
                'jumlah' => $jumlah,
                'keterangan' => $request->keterangan_detail[$index] ?? '',
            ]);
        }
    }

    private function validateStokAvailability(Request $request, ?BarangKeluar $existing = null): void
    {
        $oldQtyByBarang = [];

        if ($existing) {
            $oldQtyByBarang = BarangKeluarDetail::where('id_barang_keluar', $existing->id)
                ->get()
                ->groupBy('id_barang')
                ->map(fn ($items) => (int) $items->sum('jumlah'))
                ->toArray();
        }

        $ids = collect($request->id_barang)->merge(array_keys($oldQtyByBarang))->unique()->sort()->values();
        $locked = Barang::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        foreach ($request->id_barang as $index => $idBarang) {
            $jumlah = (int) $request->jumlah[$index];
            $barang = $locked->get($idBarang);
            $available = (int) $barang->stok + (int) ($oldQtyByBarang[$idBarang] ?? 0);

            if ($jumlah > $available) {
                throw ValidationException::withMessages(['jumlah.' . $index => 'Stok barang "' . $barang->nama . '" tidak mencukupi. Tersedia: ' . number_format($available, 0, ',', '.')]);
            }
        }
    }

    private function resolveProjectPayload(Request $request): array
    {
        if ($request->mode === 'non-unit') {
            $project = MasterProject::where('id', $request->id_project)
                ->where('jenis_pekerjaan', 'non_unit')
                ->first();

            if (!$project) {
                throw ValidationException::withMessages([
                    'id_project' => 'Master proyek yang dipilih harus berasal dari jenis pekerjaan non unit.',
                ]);
            }

            return [
                'id_project' => $project->id,
                'id_kavling' => null,
                'project_source_id' => $project->id,
            ];
        }

        $settingProyek = SettingProyek::where('id_lokasi', $request->id_lokasi)
            ->where('id_kavling', $request->id_kavling)
            ->first();

        if (!$settingProyek) {
            throw ValidationException::withMessages([
                'id_kavling' => 'Lokasi unit hanya bisa dipilih dari unit yang sudah di setting pada menu Setting Proyek.',
            ]);
        }

        return [
            'id_project' => null,
            'id_kavling' => $settingProyek->id_kavling,
            'project_source_id' => $settingProyek->id_master_project,
        ];
    }

    private function validatePekerjaanBelongsToProject(?int $projectId, $idPekerjaan): void
    {
        $exists = MasterProjectPekerjaan::query()
            ->where('id', $idPekerjaan)
            ->where('id_master_project', $projectId)
            ->exists();

        if (!$exists) {
            throw ValidationException::withMessages([
                'id_master_project_pekerjaan' => 'Jenis pekerjaan harus sesuai dengan master project yang dipilih.',
            ]);
        }
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
