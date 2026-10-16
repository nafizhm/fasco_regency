<?php
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\Customer;
use App\Models\JenisBerkas;
use App\Models\PersyaratanLegal;
use App\Http\Controllers\Legal\BerkasPengajuanController;
use App\Models\UploudFile;
use App\Traits\LogAktivitasTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class UploudFileController extends Controller
{
    use LogAktivitasTrait;
    public function index()
    {
        $data        = UploudFile::first();
        $permissions = HakAksesController::getUserPermissions();
        $customer    = Customer::where('stt_arsip', 0)->get();

        $jenisBerkas = JenisBerkas::where('aktif', 1)->orderBy('urutan')->orderBy('nama')->get();
        return view('admin.customer.uploud_file.index', compact('data', 'permissions', 'customer', 'jenisBerkas'));
    }
    
    public function edit(Request $request, $id)
    {
        $nasabah = Customer::with(['lokasiKavling', 'kavlingPeta'])->find($id);

        if (! $nasabah) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Nasabah tidak ditemukan',
            ], 404);
        }

        if ($request->ajax() && $request->get('type') === 'files') {
            $data = UploudFile::where('id_customer', $id)->orderBy('id', 'desc')->get();
            foreach ($data as $file) $file->file_url = asset('assets/customer/' . $file->lampiran);
            $jenis = JenisBerkas::pluck('nama', 'id');
            foreach (PersyaratanLegal::where('id_customer', $id)->get() as $legal) {
                foreach ($legal->file_jenis_berkas ?? [] as $jenisId => $file) {
                    $data->push((object) [
                        'id' => 'legal-' . $legal->id . '-' . $jenisId,
                        'nama_file' => ($jenis[$jenisId] ?? 'Berkas') . ' (Legal)',
                        'lampiran' => $file['name'],
                        'file_url' => route('pengajuan-berkas.file', [$legal->id, $jenisId]),
                        'delete_url' => route('pengajuan-berkas.delete-file', [$legal->id, $jenisId]),
                    ]);
                }
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('lampiran', function ($row) {
                    return $row->lampiran;
                })
                ->addColumn('action', function ($row) {
                    $deleteUrl = $row->delete_url ?? route('upload-file.destroy', $row->id);

                    $btn = '<div class="d-flex justify-content-center">';
                    $btn .= '<form action="' . e($deleteUrl) . '" method="POST" style="display:inline;">'
                    . csrf_field() . method_field('DELETE')
                        . '<button type="submit" class="delete-button btn btn-danger btn-sm ml-2">Hapus</button></form>';
                    $btn .= '</div>';

                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return response()->json([
            'status'           => 'success',
            'nik'              => $nasabah->nik,
            'lokasi_perumahan' => $nasabah->lokasiKavling ? $nasabah->lokasiKavling->nama_kavling : null,
            'no_telp'          => $nasabah->no_telp,
            'lokasi_kav_blok'  => $nasabah->kavlingPeta ? $nasabah->kavlingPeta->kode_kavling : null,
        ]);
    }

    public function update(Request $request, $id)
    {
        Customer::findOrFail($id);
        if ($request->filled('jenis_berkas_id')) {
            $request->validate(['jenis_berkas_id' => 'required|exists:jenis_berkas,id', 'lampiran' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240']);
            $legal = PersyaratanLegal::firstOrCreate(['id_customer' => $id]);
            $statuses = [];
            foreach (JenisBerkas::where('aktif', 1)->pluck('id') as $jenisId) $statuses[$jenisId] = ($legal->status_jenis_berkas ?? [])[$jenisId] ?? 0;
            $forward = Request::create('/', 'PUT', ['status_berkas' => $statuses, 'catatan_kekurangan' => $legal->catatan_kekurangan]);
            $forward->files->set('file_berkas', [$request->jenis_berkas_id => $request->file('lampiran')]);
            try {
                return app(BerkasPengajuanController::class)->update($forward, $legal->id);
            } catch (\Illuminate\Validation\ValidationException $e) {
                throw \Illuminate\Validation\ValidationException::withMessages(['lampiran' => collect($e->errors())->flatten()->all()]);
            }
        }

        $rules = [
            'nama_file' => 'required',
            'lampiran'  => 'required|mimes:jpg,jpeg,png,pdf|max:2048',
        ];

        $messages = [
            'nama_file.required' => 'Nama file wajib diisi.',
            'lampiran.required'  => 'Lampiran wajib diisi.',

            'lampiran.mimes'     => 'Lampiran harus berformat JPG, JPEG, PNG, atau PDF.',
            'lampiran.max'       => 'Ukuran lampiran maksimal 2 MB.',
        ];

        $request->validate($rules, $messages);

        DB::beginTransaction();
        try {

            if ($request->hasFile('lampiran')) {
                $lampiran = $request->file('lampiran');
                $ext      = $lampiran->getClientOriginalExtension();
                $filename = Str::random(25) . '.' . $ext;
                $lampiran->move(public_path('assets/customer/'), $filename);
            }

            $db = [
                'tanggal'     => Carbon::now(),
                'lampiran'    => $filename,
                'id_customer' => $id,
                'nama_file'   => $request->nama_file,
                'keterangan'  => $request->keterangan ?? '',
            ];

            $up = UploudFile::create($db);
            $this->logEdit('Upload File', $up->id);

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'error'  => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $data = UploudFile::findOrFail($id);

            if (! empty($data->lampiran) && file_exists(public_path('assets/customer/' . $data->lampiran))) {
                unlink(public_path('assets/customer/' . $data->lampiran));
            }

            $this->logDelete('Upload File', $data->id);
            $data->delete();

            DB::commit();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'error'  => $e->getMessage(),
            ], 500);
        }
    }
}
