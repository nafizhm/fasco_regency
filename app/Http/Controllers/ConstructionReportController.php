<?php

namespace App\Http\Controllers;

use App\Models\LokasiKavling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ConstructionReportController extends Controller
{
    private function allowed(string $action = 'lihat'): bool
    {
        return DB::table('hak_akses')->join('menu', 'menu.id', '=', 'hak_akses.id_menu')
            ->where('hak_akses.id_user', auth()->id())->where('menu.route_name', 'construction.index')
            ->where('hak_akses.lihat', 1)->where('hak_akses.'.$action, 1)->exists();
    }

    private function authorizeAccess(string $action = 'lihat'): void
    {
        abort_unless($this->allowed($action), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeAccess();
        $request->validate(['location' => 'nullable|integer', 'cluster' => 'nullable|string|max:255', 'q' => 'nullable|string|max:100', 'status' => 'nullable|in:missing,active,done,weekly']);
        $latest = DB::table('construction_reports as current')->select('current.*')->whereNotExists(function ($query) {
            $query->selectRaw('1')->from('construction_reports as newer')->whereColumn('newer.unit_id', 'current.unit_id')
                ->where(function ($dates) {
                    $dates->whereColumn('newer.reported_at', '>', 'current.reported_at')->orWhere(function ($same) {
                        $same->whereColumn('newer.reported_at', 'current.reported_at')->whereColumn('newer.id', '>', 'current.id');
                    });
                });
        });
        $week = now('Asia/Jakarta')->startOfWeek()->toDateString();
        $base = DB::table('kavling_peta as unit')->leftJoin('lokasi_kavling as location', 'location.id', '=', 'unit.id_lokasi')
            ->leftJoinSub($latest, 'latest', 'latest.unit_id', '=', 'unit.id')
            ->when($request->filled('location'), fn ($query) => $query->where('unit.id_lokasi', $request->integer('location')))
            ->when($request->filled('cluster'), fn ($query) => $query->whereRaw("COALESCE(NULLIF(TRIM(unit.nama_cluster), ''), '') = ?", [$request->input('cluster') === '__unassigned' ? '' : $request->input('cluster')]))
            ->when($request->filled('q'), fn ($query) => $query->where('unit.kode_kavling', 'like', '%'.$request->input('q').'%'));
        $stats = (clone $base)->selectRaw('COUNT(*) as total, SUM(CASE WHEN latest.percentage = 100 THEN 1 ELSE 0 END) as done, SUM(CASE WHEN latest.reported_at >= ? THEN 1 ELSE 0 END) as weekly', [$week])->first();
        $units = (clone $base)->when($request->status === 'missing', fn ($query) => $query->whereNull('latest.id'))
            ->when($request->status === 'active', fn ($query) => $query->where('latest.percentage', '<', 100))
            ->when($request->status === 'done', fn ($query) => $query->where('latest.percentage', 100))
            ->when($request->status === 'weekly', fn ($query) => $query->where(function ($pending) use ($week) {
                $pending->whereNull('latest.id')->orWhere('latest.reported_at', '<', $week);
            }))
            ->select('unit.id', 'unit.kode_kavling', 'unit.nama_cluster', 'location.nama_kavling', 'latest.percentage', 'latest.reported_at', 'latest.notes', 'latest.photos', 'latest.id as report_id')
            ->orderBy('location.nama_kavling')->orderBy('unit.nama_cluster')->orderBy('unit.kode_kavling')->paginate(18)->withQueryString();
        $locations = LokasiKavling::with(['masterSvg', 'kavlingPeta' => fn ($query) => $query->orderBy('kode_kavling')])->orderBy('urutan')->get();
        $progressMap = (clone $latest)->get()->keyBy('unit_id');
        $canUpload = $this->allowed('tambah');
        return view('admin.construction.index', compact('units', 'stats', 'locations', 'progressMap', 'canUpload', 'week'));
    }

    public function history(int $unit)
    {
        $this->authorizeAccess();
        $record = DB::table('kavling_peta as unit')->leftJoin('lokasi_kavling as location', 'location.id', '=', 'unit.id_lokasi')
            ->where('unit.id', $unit)->select('unit.id', 'unit.kode_kavling', 'unit.nama_cluster', 'location.nama_kavling')->first();
        abort_unless($record, 404);
        $reports = DB::table('construction_reports')->where('unit_id', $unit)->orderByDesc('reported_at')->orderByDesc('id')->paginate(8);
        $reports->getCollection()->transform(function ($report) {
            $report->photos = collect(json_decode($report->photos, true))->map(fn ($photo, $index) => [
                'url' => route('construction.photo', [$report->id, $index]), 'name' => $photo['name'],
            ])->values();
            return $report;
        });
        return response()->json(['unit' => $record, 'reports' => $reports, 'can_delete' => $this->allowed('hapus')]);
    }

    public function begin(Request $request)
    {
        $this->authorizeAccess('tambah');
        $data = $request->validate(['unit_id' => 'required|integer|exists:kavling_peta,id', 'reported_at' => 'required|date_format:Y-m-d|before_or_equal:'.now('Asia/Jakarta')->toDateString(),
            'percentage' => 'required|numeric|min:0|max:100', 'notes' => 'required|string|max:10000', 'photos' => 'required|array|min:1|max:50',
            'photos.*.name' => 'required|string|max:255', 'photos.*.size' => 'required|integer|min:1']);
        $this->validatePercentage($data);
        foreach ($data['photos'] as &$photo) {
            $photo['size'] = (int) $photo['size'];
        }
        unset($photo);
        $id = (string) Str::uuid();
        File::ensureDirectoryExists(storage_path('app/construction-uploads/'.$id));
        File::put(storage_path('app/construction-uploads/'.$id.'/metadata.json'), json_encode($data + ['user_id' => auth()->id()]));
        return response()->json(['id' => $id]);
    }

    private function validatePercentage(array $data): void
    {
        $latest = DB::table('construction_reports')->where('unit_id', $data['unit_id'])
            ->orderByDesc('reported_at')->orderByDesc('id')->first();
        if ($latest && (float) $data['percentage'] < (float) $latest->percentage) {
            throw ValidationException::withMessages([
                'percentage' => 'Persentase tidak boleh lebih rendah dari progres terakhir ('.(float) $latest->percentage.'%). Pilih ulang unit untuk memperbarui batas persentase.',
            ]);
        }
    }

    private function metadata(string $id): array
    {
        abort_unless(Str::isUuid($id), 404);
        $path = storage_path('app/construction-uploads/'.$id);
        abort_unless(is_file($path.'/metadata.json'), 404);
        $data = json_decode(File::get($path.'/metadata.json'), true);
        abort_unless((string) $data['user_id'] === (string) auth()->id(), 403);
        return [$path, $data];
    }

    public function chunk(Request $request, string $id, int $photo)
    {
        $this->authorizeAccess('tambah');
        [$path, $data] = $this->metadata($id);
        abort_unless(isset($data['photos'][$photo]), 404);
        abort_if(DB::table('construction_reports')->where('upload_id', $id)->exists(), 409);
        $request->validate(['offset' => 'required|integer|min:0', 'chunk' => 'required|file|max:2048']);
        $handle = fopen($path.'/'.$photo.'.part', 'c+b');
        abort_unless($handle !== false, 500);
        try {
            flock($handle, LOCK_EX);
            $offset = (int) $request->input('offset');
            $length = $request->file('chunk')->getSize();
            abort_if($offset > fstat($handle)['size'] || $offset + $length > $data['photos'][$photo]['size'], 409);
            fseek($handle, $offset);
            $source = fopen($request->file('chunk')->getRealPath(), 'rb');
            try {
                $written = stream_copy_to_stream($source, $handle);
            } finally {
                fclose($source);
            }
            abort_unless($written === $length, 500, 'Penyimpanan gagal. Silakan coba lagi.');
            fflush($handle);
            return response()->json(['offset' => $offset + $written]);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function finish(string $id)
    {
        $this->authorizeAccess('tambah');
        [$path, $data] = $this->metadata($id);
        $lock = fopen($path.'/finish.lock', 'c+b');
        abort_unless($lock !== false, 500);
        try {
            flock($lock, LOCK_EX);
            $existing = DB::table('construction_reports')->where('upload_id', $id)->first();
            if ($existing) {
                return response()->json(['message' => 'Laporan berhasil disimpan.', 'unit_id' => $existing->unit_id]);
            }
            $photos = [];
            foreach ($data['photos'] as $index => $photo) {
                $file = $path.'/'.$index.'.part';
                clearstatcache(true, $file);
                abort_unless(is_file($file) && filesize($file) === (int) $photo['size'], 422, 'Foto '.($index + 1).' belum selesai diupload.');
                $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file);
                abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif', 'image/heic', 'image/heif'], true), 422, 'Foto '.($index + 1).' bukan gambar yang didukung. Gunakan JPG, PNG, WEBP, GIF, AVIF, atau HEIC.');
                $photos[] = ['name' => basename($photo['name']), 'mime' => $mime, 'size' => (int) $photo['size']];
            }
            $report = DB::transaction(function () use ($id, $data, $photos) {
                DB::table('kavling_peta')->where('id', $data['unit_id'])->lockForUpdate()->firstOrFail();
                $this->validatePercentage($data);
                return DB::table('construction_reports')->insertGetId(['upload_id' => $id, 'unit_id' => $data['unit_id'], 'user_id' => $data['user_id'],
                    'reported_at' => $data['reported_at'], 'percentage' => $data['percentage'], 'notes' => $data['notes'], 'photos' => json_encode($photos), 'created_at' => now(), 'updated_at' => now()]);
            });
            return response()->json(['message' => 'Laporan berhasil disimpan.', 'id' => $report, 'unit_id' => $data['unit_id']]);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function destroy(int $report)
    {
        $this->authorizeAccess('hapus');
        $record = DB::table('construction_reports')->find($report);
        abort_unless($record, 404);
        DB::transaction(function () use ($record, $report) {
            DB::table('kavling_peta')->where('id', $record->unit_id)->lockForUpdate()->firstOrFail();
            DB::table('construction_reports')->where('id', $report)->delete();
        });
        // Remove only this report's known files, never recursively delete a computed path.
        if (Str::isUuid($record->upload_id)) {
            $path = storage_path('app/construction-uploads/'.$record->upload_id);
            foreach (array_keys(json_decode($record->photos, true)) as $index) {
                File::delete($path.'/'.(int) $index.'.part');
            }
            File::delete($path.'/metadata.json');
        }
        return response()->json(['message' => 'Laporan dan foto berhasil dihapus.']);
    }

    public function photo(int $report, int $photo)
    {
        $this->authorizeAccess();
        $record = DB::table('construction_reports')->find($report);
        abort_unless($record, 404);
        $photos = json_decode($record->photos, true);
        abort_unless(isset($photos[$photo]), 404);
        return response()->file(storage_path('app/construction-uploads/'.$record->upload_id.'/'.$photo.'.part'),
            ['Content-Type' => $photos[$photo]['mime'], 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, max-age=3600']);
    }
}
