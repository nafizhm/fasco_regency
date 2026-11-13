<?php

namespace App\Http\Controllers\Pengaturan;

use App\Http\Controllers\Controller;
use App\Models\Tukang;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AbsenTukangController extends Controller
{
    use \App\Traits\InventoryAccess;

    public function index(Request $request)
    {
        $this->authorizeInventory('absen-tukang.index', 'lihat');
        $request->validate(['minggu' => 'nullable|date_format:Y-m-d']);
        $start = CarbonImmutable::parse($request->input('minggu', now('Asia/Jakarta')->toDateString()))->startOfWeek();
        $week = DB::table('absen_tukang_pekan')->where('mulai', $start->toDateString())->first();
        $saved = $week ? DB::table('absen_tukang_detail')->where('id_pekan', $week->id)->get()->keyBy('id_tukang') : collect();
        $workers = Tukang::orderBy('id')->get();
        $permissions = HakAksesController::getUserPermissions();

        return view('admin.pengaturan.tukang.absen', compact('start', 'week', 'saved', 'workers', 'permissions'));
    }

    public function store(Request $request)
    {
        $this->authorizeInventory('absen-tukang.index', 'lihat');
        $request->validate([
            'mulai' => 'required|date_format:Y-m-d',
            'versi' => 'required|integer|min:0',
            'rows' => 'required|array|min:1|max:1000',
            'rows.*.id' => 'required|integer|distinct|exists:tukang,id',
            'rows.*.hari' => 'required|array|size:6',
            'rows.*.hari.*' => ['nullable', 'string', 'regex:/^(M|H|I|S|A|L|0\.5|½|([01][0-9]|2[0-3]):[0-5][0-9])$/i'],
        ], ['rows.*.hari.*.regex' => 'Isi dengan jam HH:MM, M (masuk), ½, I (izin), S (sakit), A (alpa), atau L (libur).']);
        $start = CarbonImmutable::parse($request->mulai)->startOfWeek()->toDateString();
        abort_unless($request->mulai === $start, 422, 'Periode harus dimulai hari Senin.');
        $existing = DB::table('absen_tukang_pekan')->where('mulai', $start)->exists();
        $this->authorizeInventory('absen-tukang.index', $existing ? 'edit' : 'tambah');

        DB::transaction(function () use ($request, $start) {
            DB::table('absen_tukang_pekan')->insertOrIgnore([
                'mulai' => $start, 'versi' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $week = DB::table('absen_tukang_pekan')->where('mulai', $start)->lockForUpdate()->first();
            $this->authorizeInventory('absen-tukang.index', $week->versi > 0 ? 'edit' : 'tambah');
            if ((int) $week->versi !== (int) $request->versi) {
                throw ValidationException::withMessages(['versi' => 'Rekap telah diubah pengguna lain. Muat ulang halaman sebelum menyimpan.']);
            }
            $workers = Tukang::whereIn('id', array_column($request->rows, 'id'))->lockForUpdate()->get()->keyBy('id');
            foreach ($request->rows as $row) {
                $days = [];
                for ($day = 0; $day < 6; $day++) {
                    if (! array_key_exists($day, $row['hari'])) {
                        throw ValidationException::withMessages(['rows' => 'Urutan hari tidak valid.']);
                    }
                    $days[] = strtoupper($row['hari'][$day] ?? '');
                }
                $saved = DB::table('absen_tukang_detail')->where('id_pekan', $week->id)->where('id_tukang', $row['id'])->first();
                DB::table('absen_tukang_detail')->updateOrInsert(['id_pekan' => $week->id, 'id_tukang' => $row['id']], [
                    'gaji_per_hari' => $saved ? $saved->gaji_per_hari : $workers[$row['id']]->gaji_per_hari,
                    'hari' => json_encode($days, JSON_THROW_ON_ERROR),
                ]);
            }
            DB::table('absen_tukang_pekan')->where('id', $week->id)->update(['versi' => $week->versi + 1, 'updated_at' => now()]);
        });

        return response()->json(['message' => 'Absensi pekan berhasil disimpan.']);
    }
}
