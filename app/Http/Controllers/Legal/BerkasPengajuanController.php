<?php
namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\Customer;
use App\Models\UploudFile;
use App\Models\JenisBerkas;
use App\Models\PersyaratanLegal;
use App\Traits\LogAktivitasTrait;
use App\Services\LegalBerkasPdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class BerkasPengajuanController extends Controller
{
    use LogAktivitasTrait;
    public function index(Request $request)
    {
        $permissions = HakAksesController::getUserPermissions();
        $jenisBerkas = JenisBerkas::where('aktif', 1)->orderBy('urutan')->orderBy('nama')->get();

        if ($request->ajax()) {
            $data = PersyaratanLegal::with('customer')->orderBy('id', 'desc');

            $table = DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('nama_customer', function ($row) {
                    return $row->customer?->nama_lengkap ?? '';
                });

            foreach ($jenisBerkas as $jenis) {
                $table->addColumn('berkas_' . $jenis->id, function ($row) use ($jenis) {
                    $status = (int) (($row->status_jenis_berkas ?? [])[$jenis->id] ?? 0);
                    return $status === 1
                        ? '<i class="fas fa-check-circle text-success"></i>'
                        : '<i class="fas fa-times-circle text-danger"></i>';
                });
            }

            return $table

                ->addColumn('action', function ($row) use ($permissions): string {
                    $editUrl = route('pengajuan-berkas.edit', $row->id);
                    $hasCust = $row->customer ? true : false;

                    $btn = '<div class="d-flex justify-content-center">';
                    if ($permissions['edit']) {
                        $btn .= '<button class="btn btn-primary btn-sm edit-button ' . ($hasCust ? '' : 'disabled') . '" data-id="' . e($row->id) . '" data-url="' . e($editUrl) . '">Edit</button>';

                    }

                    if (!empty($row->file_jenis_berkas)) {
                        $btn .= '<a class="btn btn-dark btn-sm ml-1 print-berkas" href="' . e(route('pengajuan-berkas.print', $row->id)) . '">Print PDF</a>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(array_merge(['nama_customer', 'action'], $jenisBerkas->map(fn ($jenis) => 'berkas_' . $jenis->id)->all()))
                ->make(true);
        }

        return view('admin.legal.pengajuan_berkas.index', compact('permissions', 'jenisBerkas'));
    }

    public function edit($id)
    {
        $list = PersyaratanLegal::with('customer')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $list,
            'customer_files' => UploudFile::where('id_customer', $list->id_customer)->get(['id', 'nama_file', 'lampiran']),
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = PersyaratanLegal::findOrFail($id);

        $request->validate([
            'status_berkas'      => 'required|array',
            'status_berkas.*'    => 'required|in:0,1',
            'catatan_kekurangan' => 'nullable',
            'hapus_berkas' => 'nullable|array',
            'hapus_berkas.*' => 'integer|exists:jenis_berkas,id',
            'pilih_berkas' => 'nullable|array',
            'pilih_berkas.*' => 'nullable|integer',
            'file_berkas' => 'nullable|array',
            'file_berkas.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'percakapan_wa'      => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
        ], [
            'status_berkas.required'   => 'Status jenis berkas wajib diisi!',
            'status_berkas.*.required' => 'Setiap status jenis berkas wajib dipilih!',
            'percakapan_wa.file'      => 'File percakapan WA harus berupa file!',
            'percakapan_wa.mimes'     => 'File percakapan WA harus berupa gambar dengan format jpeg, png, atau jpg!',
            'percakapan_wa.max'       => 'Ukuran file percakapan WA maksimal 2MB!',
        ]);

        $allowedIds = JenisBerkas::where('aktif', 1)->pluck('id')->map(fn ($id) => (string) $id)->all();
        foreach (array_unique(array_merge(array_keys($request->input('status_berkas', [])), array_keys($request->file('file_berkas', [])))) as $jenisId) {
            if (!in_array((string) $jenisId, $allowedIds, true)) {
                throw ValidationException::withMessages(['status_berkas' => 'Jenis berkas tidak valid.']);
            }
        }
        $selectedFiles = [];
        foreach ($request->input('pilih_berkas', []) as $jenisId => $uploadId) {
            if (!$uploadId || $request->hasFile('file_berkas.' . $jenisId)) continue;
            if (!in_array((string) $jenisId, $allowedIds, true)) throw ValidationException::withMessages(['pilih_berkas.' . $jenisId => 'Jenis berkas tidak valid.']);
            $upload = UploudFile::where('id_customer', $data->id_customer)->find($uploadId);
            if (!$upload || !is_file(public_path('assets/customer/' . basename($upload->lampiran)))) {
                throw ValidationException::withMessages(['pilih_berkas.' . $jenisId => 'Berkas customer tidak ditemukan.']);
            }
            $selectedFiles[$jenisId] = $upload;
        }
        // Check PDF compatibility before storing files, so every accepted upload can be printed.
        foreach ($request->file('file_berkas', []) as $jenisId => $file) {
            if ($file->getMimeType() === 'application/pdf') {
                try {
                    $pdf = app(LegalBerkasPdf::class)->create();
                    $pages = $pdf->setSourceFile($file->getRealPath());
                    for ($page = 1; $page <= $pages; $page++) $pdf->importPage($page);
                } catch (\Exception $e) {
                    throw ValidationException::withMessages(['file_berkas.' . $jenisId => 'PDF tidak dapat diproses. Simpan ulang sebagai PDF tanpa enkripsi lalu upload kembali.']);
                }
            }
        }
        foreach ($selectedFiles as $jenisId => $upload) {
            $path = public_path('assets/customer/' . basename($upload->lampiran));
            if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf') {
                try {
                    $pdf = app(LegalBerkasPdf::class)->create();
                    $pages = $pdf->setSourceFile($path);
                    for ($page = 1; $page <= $pages; $page++) $pdf->importPage($page);
                } catch (\Exception $e) {
                    throw ValidationException::withMessages(['pilih_berkas.' . $jenisId => 'PDF customer tidak dapat digabung. Upload ulang PDF tanpa enkripsi.']);
                }
            }
        }
        $newPaths = [];
        $oldPaths = [];
        DB::beginTransaction();
        try {
            if ($request->hasFile('percakapan_wa')) {
                if (! empty($data->percakapan_wa) && file_exists(public_path('assets/legal/pengajuan_berkas/percakapan_wa/' . $data->percakapan_wa))) {
                    unlink(public_path('assets/legal/pengajuan_berkas/percakapan_wa/' . $data->percakapan_wa));
                }

                $foto             = $request->file('percakapan_wa');
                $ext              = $foto->getClientOriginalExtension();
                $percakapanwaName = Str::random(25) . '.' . $ext;
                $foto->move(public_path('assets/legal/pengajuan_berkas/percakapan_wa/'), $percakapanwaName);
            }

            $statusBerkas = collect($request->status_berkas)
                ->mapWithKeys(fn ($status, $jenisId) => [(string) $jenisId => (int) $status])
                ->all();

            $files = $data->file_jenis_berkas ?? [];
            foreach ($request->input('hapus_berkas', []) as $jenisId) {
                if (!empty($files[$jenisId]['path'])) $oldPaths[] = $files[$jenisId]['path'];
                unset($files[$jenisId]);
                $statusBerkas[$jenisId] = 0;
            }
            foreach ($selectedFiles as $jenisId => $upload) {
                $source = public_path('assets/customer/' . basename($upload->lampiran));
                $path = 'legal/pengajuan-berkas/' . $data->id . '/' . Str::uuid() . '.' . strtolower(pathinfo($source, PATHINFO_EXTENSION));
                if (!Storage::disk('local')->put($path, file_get_contents($source))) throw new \RuntimeException('Gagal menyimpan berkas.');
                $newPaths[] = $path;
                if (!empty($files[$jenisId]['path'])) $oldPaths[] = $files[$jenisId]['path'];
                $files[$jenisId] = ['path' => $path, 'name' => $upload->nama_file . '.' . pathinfo($source, PATHINFO_EXTENSION)];
            }
            foreach ($request->file('file_berkas', []) as $jenisId => $file) {
                $path = $file->store('legal/pengajuan-berkas/' . $data->id, 'local');
                if (!$path) throw new \RuntimeException('Gagal menyimpan berkas.');
                $newPaths[] = $path;
                if (!empty($files[$jenisId]['path'])) $oldPaths[] = $files[$jenisId]['path'];
                $files[$jenisId] = ['path' => $path, 'name' => $file->getClientOriginalName()];
            }
            $statusBerkas = array_replace($data->status_jenis_berkas ?? [], $statusBerkas);
            foreach ($files as $jenisId => $file) $statusBerkas[$jenisId] = 1;

            $update = [
                'file_jenis_berkas' => $files,
                'status_jenis_berkas' => $statusBerkas,
                'catatan_kekurangan' => $request->catatan_kekurangan ?? '',
                'percakapan_wa'      => isset($percakapanwaName) ? $percakapanwaName : $data->percakapan_wa,
            ];

            $legacyColumns = [
                'IPH' => 'IPH', 'SHGB' => 'SHGB', 'SSP' => 'SSP', 'BPHTB' => 'BPHTB',
                'SIKUMBANG' => 'SIKUMBANG', 'DAFTAR SIKASEP' => 'DAFTAR_SIKASEP',
                'FOTO SIKASEP' => 'FOTO_SIKASEP', 'TRILOGI' => 'TRILOGI',
            ];
            foreach (JenisBerkas::whereIn('nama', array_keys($legacyColumns))->get() as $jenis) {
                $update[$legacyColumns[$jenis->nama]] = $statusBerkas[$jenis->id] ?? 0;
            }

            $data->update($update);

            $this->logEdit('Berkas Pengajuan', $data->id);

            DB::commit();
            Storage::disk('local')->delete($oldPaths);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Storage::disk('local')->delete($newPaths);
            return response()->json([
                'status' => 'error',
                'error'  => $e->getMessage(),
            ], 500);
        }
    }
    public function deleteFile($id, $jenis)
    {
        $data = PersyaratanLegal::findOrFail($id);
        $statuses = [];
        foreach (JenisBerkas::where('aktif', 1)->pluck('id') as $jenisId) $statuses[$jenisId] = ($data->status_jenis_berkas ?? [])[$jenisId] ?? 0;
        $request = Request::create('/', 'PUT', ['status_berkas' => $statuses, 'hapus_berkas' => [$jenis], 'catatan_kekurangan' => $data->catatan_kekurangan]);
        return $this->update($request, $id);
    }

    public function file($id, $jenis)
    {
        $data = PersyaratanLegal::findOrFail($id);
        $file = ($data->file_jenis_berkas ?? [])[$jenis] ?? null;
        abort_unless($file && Storage::disk('local')->exists($file['path']), 404);
        return response()->file(Storage::disk('local')->path($file['path']));
    }

    public function print($id, LegalBerkasPdf $builder, Request $request)
    {
        $data = PersyaratanLegal::findOrFail($id);
        $files = $data->file_jenis_berkas ?? [];
        abort_if(empty($files), 422, 'Belum ada berkas yang diupload.');
        $pdf = $builder->create();
        foreach (JenisBerkas::orderBy('urutan')->orderBy('nama')->orderBy('id')->get() as $jenis) {
            $file = $files[$jenis->id] ?? null;
            if (!$file) continue;
            abort_unless(Storage::disk('local')->exists($file['path']), 422, 'File ' . $jenis->nama . ' tidak ditemukan. Upload ulang berkas tersebut.');
            $builder->append($pdf, Storage::disk('local')->path($file['path']), $request->boolean('ukuran_asli'));
        }
        abort_if($pdf->getNumPages() === 0, 422, 'Belum ada berkas yang dapat dicetak.');
        return response($pdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="pengajuan-berkas-' . $data->id . '.pdf"',
        ]);
    }

}
