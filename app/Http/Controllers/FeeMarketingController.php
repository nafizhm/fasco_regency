<?php

namespace App\Http\Controllers;

use App\Models\FeeMarketing;
use App\Services\FeeMarketingWords;
use App\Traits\InventoryAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FeeMarketingController extends Controller
{
    use InventoryAccess;

    public function index(Request $request)
    {
        $this->authorizeInventory('fee-marketing.index');
        $request->validate(['q' => 'nullable|string|max:150']);
        $query = FeeMarketing::query();
        if ($request->filled('q')) {
            $query->where(fn ($q) => $q->where('nomor', 'like', '%'.$request->q.'%')
                ->orWhere('data', 'like', '%'.$request->q.'%'));
        }
        $records = $query->orderByDesc('tanggal')->orderByDesc('id')->paginate(20)->withQueryString();
        $permissions = (array) DB::table('hak_akses')->join('menu', 'menu.id', '=', 'hak_akses.id_menu')
            ->where('hak_akses.id_user', auth()->id())->where('menu.route_name', 'fee-marketing.index')
            ->select('hak_akses.tambah', 'hak_akses.edit', 'hak_akses.hapus')->first();
        return view('admin.fee_marketing.index', compact('records', 'permissions'));
    }

    public function create()
    {
        $this->authorizeInventory('fee-marketing.index', 'tambah');
        $record = new FeeMarketing(['tanggal' => now('Asia/Jakarta')->toDateString(), 'data' => [
            'perusahaan' => 'PT. Dwi Raka Graha', 'subjudul' => 'DEVELOPER DAN CONTRACTOR',
            'alamat' => 'Tegal Bugis, Kelurahan Saptorenggo, Kecamatan Pakis, Kabupaten Malang',
            'telepon' => '0813-3535-2277', 'proyek' => 'Fasco Regency',
            'diterima_dari' => 'PT. Dwi Raka Graha', 'kota' => 'Malang', 'owner' => 'Laji Siswanto',
            'jabatan_penerima' => 'Leader', 'catatan_harga' => '*harga deal tidak termasuk Biaya Admin / tambahan lain-lain',
            'ketentuan' => "1. Fee diserahkan setelah tandatangan bukti pembayaran tanda terima fee marketing\n2. Fee diserahkan Secara Cash,Cek Atau Transfer ke :",
            'rincian' => [
                ['blok' => '', 'level' => 'Marketing', 'harga_deal' => 0, 'blt' => 0, 'persen' => '2.5', 'fee_sebelumnya' => 0, 'fee_saat_ini' => 0],
                ['blok' => '', 'level' => 'Leader', 'harga_deal' => 0, 'blt' => 0, 'persen' => '1.5', 'fee_sebelumnya' => 0, 'fee_saat_ini' => 0],
            ], 'riwayat' => [],
        ]]);
        return view('admin.fee_marketing.form', compact('record'));
    }

    public function edit(FeeMarketing $fee_marketing)
    {
        $this->authorizeInventory('fee-marketing.index', 'edit');
        return view('admin.fee_marketing.form', ['record' => $fee_marketing]);
    }

    private function validated(Request $request, ?FeeMarketing $record = null): array
    {
        $money = ['required', 'regex:/^[0-9]{1,13}$/', 'numeric', 'min:0', 'max:1000000000000'];
        $rules = [
            'nomor' => ['required', 'string', 'max:150', Rule::unique('fee_marketing', 'nomor')->ignore($record?->id)],
            'tanggal' => 'required|date_format:Y-m-d',
            'rincian' => 'required|array|min:1|max:100', 'rincian.*.blok' => 'required|string|max:150',
            'rincian.*.level' => 'required|string|max:100',
            'rincian.*.persen' => ['required', 'numeric', 'between:0,100', 'regex:/^[0-9]+(\\.[0-9]{1,2})?$/'],
            'riwayat' => 'nullable|array|max:100', 'riwayat.*.tanggal' => 'required|date_format:Y-m-d',
            'riwayat.*.keterangan' => 'required|string|max:500', 'riwayat.*.nominal' => $money,
        ];
        foreach (['perusahaan', 'diterima_dari', 'leader_tim', 'untuk_pembayaran', 'blok_skema', 'nama_user', 'kota', 'owner', 'proyek', 'jabatan_penerima'] as $field) {
            $rules[$field] = 'required|string|max:500';
        }
        foreach (['subjudul', 'alamat', 'telepon', 'bank_rekening', 'atas_nama', 'catatan_harga', 'ketentuan'] as $field) {
            $rules[$field] = 'nullable|string|max:3000';
        }
        foreach (['harga_deal', 'blt', 'fee_sebelumnya', 'fee_saat_ini'] as $field) $rules['rincian.*.'.$field] = $money;
        $data = $request->validate($rules);
        $data['rincian'] = array_values(array_map(function ($row) {
            foreach (['harga_deal', 'blt', 'fee_sebelumnya', 'fee_saat_ini'] as $field) $row[$field] = (int) $row[$field];
            $basisPoints = (int) round((float) $row['persen'] * 100);
            $row['komisi'] = intdiv($row['harga_deal'] * $basisPoints + 5000, 10000);
            $row['total_fee'] = $row['blt'] + $row['komisi'];
            return $row;
        }, $data['rincian']));
        $data['riwayat'] = array_values(array_map(function ($row) {
            $row['nominal'] = (int) $row['nominal'];
            return $row;
        }, $data['riwayat'] ?? []));
        $jumlah = array_sum(array_column($data['rincian'], 'fee_saat_ini'));
        $result = ['nomor' => $data['nomor'], 'tanggal' => $data['tanggal'], 'jumlah' => $jumlah];
        unset($data['nomor'], $data['tanggal']);
        return $result + ['data' => $data];
    }

    public function store(Request $request)
    {
        $this->authorizeInventory('fee-marketing.index', 'tambah');
        $record = FeeMarketing::create($this->validated($request));
        return redirect()->route('fee-marketing.show', $record)->with('success', 'Fee Marketing berhasil disimpan.');
    }

    public function update(Request $request, FeeMarketing $fee_marketing)
    {
        $this->authorizeInventory('fee-marketing.index', 'edit');
        $fee_marketing->update($this->validated($request, $fee_marketing));
        return redirect()->route('fee-marketing.show', $fee_marketing)->with('success', 'Fee Marketing berhasil diperbarui.');
    }

    public function show(FeeMarketing $fee_marketing)
    {
        $this->authorizeInventory('fee-marketing.index');
        return view('admin.fee_marketing.show', ['record' => $fee_marketing]);
    }

    public function pdf(FeeMarketing $fee_marketing)
    {
        $this->authorizeInventory('fee-marketing.index');
        return Pdf::loadView('admin.fee_marketing.pdf', [
            'record' => $fee_marketing,
            'd' => $fee_marketing->data,
            'terbilang' => FeeMarketingWords::rupiah($fee_marketing->jumlah),
        ])->setPaper('a4', 'portrait')->stream('fee-marketing-'.$fee_marketing->id.'.pdf');
    }

    public function destroy(FeeMarketing $fee_marketing)
    {
        $this->authorizeInventory('fee-marketing.index', 'hapus');
        $fee_marketing->delete();
        return redirect()->route('fee-marketing.index')->with('success', 'Fee Marketing berhasil dihapus.');
    }
}

