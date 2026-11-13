<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\Spk;
use App\Services\SpkWordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SpkController extends Controller
{
    use \App\Traits\InventoryAccess;

    public function index(Request $request)
    {
        $this->authorizeInventory('spk.index', 'lihat');
        $request->validate(['q' => 'nullable|string|max:255', 'status' => 'nullable|in:belum,sebagian,lunas']);
        $query = Spk::withSum('pembayaran', 'nominal');
        if ($request->filled('q')) {
            $query->where(fn ($q) => $q->where('nomor', 'like', '%'.$request->q.'%')->orWhere('nama_pemborong', 'like', '%'.$request->q.'%')->orWhere('nama_proyek', 'like', '%'.$request->q.'%'));
        }
        $paidSql = '(select coalesce(sum(nominal), 0) from spk_pembayaran where id_spk = spk.id)';
        if ($request->status === 'belum') $query->whereRaw("$paidSql = 0");
        if ($request->status === 'sebagian') $query->whereRaw("$paidSql > 0 AND $paidSql < nilai_borongan");
        if ($request->status === 'lunas') $query->whereRaw("$paidSql >= nilai_borongan");
        $data = $query->orderByDesc('id')->paginate(20)->withQueryString();
        $permissions = HakAksesController::getUserPermissions();

        return view('admin.spk.index', compact('data', 'permissions'));
    }

    public function create()
    {
        $this->authorizeInventory('spk.index', 'tambah');
        $spk = new Spk([
            'tanggal' => now('Asia/Jakarta')->toDateString(), 'tanggal_mulai' => now('Asia/Jakarta')->toDateString(),
            'nama_perusahaan' => 'PT. DWI RAKA GRAHA', 'alamat_perusahaan' => 'Tegal Bugis, Saptorenggo – Pakis, Malang',
            'telp_perusahaan' => '0813-3535-2277', 'penandatangan' => 'Laji Siswanto', 'kota' => 'Malang',
            'jabatan_pemborong' => 'Pemborong', 'nama_proyek' => 'FASCO REGENCY',
            'lokasi_proyek' => 'Boro Bugis, Saptorenggo Pakis Malang', 'skema_pembayaran' => 'Sesuai Kesepakatan',
            'items' => [['item' => '', 'volume' => 1, 'satuan' => '', 'harga_satuan' => 0]],
        ]);

        return view('admin.spk.form', compact('spk'));
    }

    private function validated(Request $request, ?Spk $spk = null): array
    {
        $rules = [
            'nomor' => ['required', 'string', 'max:255', Rule::unique('spk', 'nomor')->ignore($spk?->id)],
            'tanggal' => 'required|date_format:Y-m-d', 'tanggal_mulai' => 'required|date_format:Y-m-d',
            'tanggal_selesai' => 'nullable|date_format:Y-m-d|after_or_equal:tanggal_mulai',
            'nilai_borongan' => 'nullable|integer|min:1|max:100000000000000',
            'items' => 'required|array|min:1|max:100',
            'items.*.item' => 'required|string|max:1000', 'items.*.satuan' => 'required|string|max:50',
            'items.*.volume' => ['required', 'numeric', 'min:0.001', 'max:100000', 'regex:/^\d+(\.\d{1,3})?$/'],
            'items.*.harga_satuan' => 'required|integer|min:0|max:100000000',
        ];
        foreach (['nama_perusahaan', 'penandatangan', 'kota', 'nama_pemborong', 'jabatan_pemborong', 'nama_proyek', 'lama_pengerjaan'] as $field) $rules[$field] = 'required|string|max:255';
        $rules['telp_perusahaan'] = 'required|string|max:50';
        foreach (['alamat_perusahaan', 'alamat_pemborong', 'lokasi_proyek', 'jenis_pekerjaan', 'skema_pembayaran'] as $field) $rules[$field] = 'required|string|max:5000';
        $data = $request->validate($rules);
        $data['items'] = array_values(array_map(fn ($item) => [
            'item' => $item['item'], 'volume' => $item['volume'], 'satuan' => $item['satuan'],
            'harga_satuan' => (int) $item['harga_satuan'], 'total' => (int) round((float) $item['volume'] * (int) $item['harga_satuan']),
        ], $data['items']));
        $data['total_rincian'] = array_sum(array_column($data['items'], 'total'));
        $data['nilai_borongan'] = (int) ($data['nilai_borongan'] ?? $data['total_rincian']);
        if ($data['nilai_borongan'] < 1) throw ValidationException::withMessages(['nilai_borongan' => 'Nilai borongan harus lebih dari nol.']);

        return $data;
    }

    public function store(Request $request)
    {
        $this->authorizeInventory('spk.index', 'tambah');
        $spk = Spk::create($this->validated($request));

        return redirect()->route('spk.show', $spk)->with('success', 'SPK berhasil dibuat.');
    }

    public function show(Spk $spk)
    {
        $this->authorizeInventory('spk.index', 'lihat');
        $spk->load(['pembayaran' => fn ($q) => $q->orderBy('tanggal')->orderBy('id')]);
        $permissions = $this->permissions();

        return view('admin.spk.show', compact('spk', 'permissions'));
    }

    private function permissions(): array
    {
        $row = DB::table('hak_akses')->join('menu', 'menu.id', '=', 'hak_akses.id_menu')
            ->where('menu.route_name', 'spk.index')->where('id_user', auth()->id())->select('hak_akses.*')->first();
        return ['tambah' => $row?->tambah ?? 0, 'edit' => $row?->edit ?? 0, 'hapus' => $row?->hapus ?? 0];
    }

    public function edit(Spk $spk)
    {
        $this->authorizeInventory('spk.index', 'edit');

        return view('admin.spk.form', compact('spk'));
    }

    public function update(Request $request, Spk $spk)
    {
        $this->authorizeInventory('spk.index', 'edit');
        $data = $this->validated($request, $spk);
        $request->validate(['versi' => 'required|integer|min:0']);
        DB::transaction(function () use ($spk, $data, $request) {
            $locked = Spk::lockForUpdate()->findOrFail($spk->id);
            if ((int) $locked->versi !== (int) $request->versi) throw ValidationException::withMessages(['versi' => 'SPK telah berubah. Muat ulang sebelum mengedit.']);
            if ($data['nilai_borongan'] < $locked->pembayaran()->sum('nominal')) throw ValidationException::withMessages(['nilai_borongan' => 'Nilai SPK tidak boleh lebih kecil dari pembayaran yang sudah dicatat.']);
            $firstPayment = $locked->pembayaran()->min('tanggal');
            if ($firstPayment && $data['tanggal'] > $firstPayment) throw ValidationException::withMessages(['tanggal' => 'Tanggal SPK tidak boleh sesudah pembayaran yang sudah dicatat.']);
            $locked->update($data + ['versi' => $locked->versi + 1]);
        });

        return redirect()->route('spk.show', $spk)->with('success', 'SPK berhasil diperbarui.');
    }

    public function destroy(Spk $spk)
    {
        $this->authorizeInventory('spk.index', 'hapus');
        DB::transaction(function () use ($spk) {
            $locked = Spk::lockForUpdate()->findOrFail($spk->id);
            if ($locked->pembayaran()->exists()) throw ValidationException::withMessages(['spk' => 'SPK yang sudah memiliki pembayaran tidak dapat dihapus.']);
            $locked->delete();
        });

        return redirect()->route('spk.index')->with('success', 'SPK berhasil dihapus.');
    }

    public function payment(Request $request, Spk $spk)
    {
        $this->authorizeInventory('spk.index', 'edit');
        $data = $request->validate([
            'tanggal' => 'required|date_format:Y-m-d', 'nominal' => 'required|integer|min:1|max:100000000000000',
            'metode' => 'nullable|string|max:100', 'referensi' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string|max:5000', 'token' => 'required|uuid',
        ]);
        DB::transaction(function () use ($spk, $data) {
            $locked = Spk::lockForUpdate()->findOrFail($spk->id);
            if ($locked->pembayaran()->where('token', $data['token'])->exists()) return;
            if ($data['tanggal'] < $locked->tanggal->format('Y-m-d')) throw ValidationException::withMessages(['tanggal' => 'Tanggal pembayaran tidak boleh sebelum tanggal SPK.']);
            $remaining = (int) $locked->nilai_borongan - (int) $locked->pembayaran()->sum('nominal');
            if ($data['nominal'] > $remaining) throw ValidationException::withMessages(['nominal' => 'Pembayaran melebihi sisa tagihan SPK.']);
            $locked->pembayaran()->create($data);
        });

        return redirect()->route('spk.show', $spk)->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function word(Spk $spk, SpkWordService $service)
    {
        $this->authorizeInventory('spk.index', 'lihat');
        $path = $service->generate($spk);

        return response()->download($path, 'SPK-'.preg_replace('/[^A-Za-z0-9_-]/', '-', $spk->nomor).'.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }
}
