<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\PengajuanPembelian;
use App\Models\Supplier;
use App\Services\PurchaseReceivingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PengajuanPembelianController extends Controller
{
    use \App\Traits\InventoryAccess;

    private function permissions(): array
    {
        $access = DB::table('hak_akses')->join('menu', 'menu.id', '=', 'hak_akses.id_menu')
            ->where('menu.route_name', 'pengajuan-pembelian.index')->where('hak_akses.id_user', auth()->id())
            ->select('hak_akses.tambah', 'hak_akses.edit', 'hak_akses.hapus')->first();

        return (array) $access;
    }

    public function index()
    {
        $this->authorizeInventory('pengajuan-pembelian.index');
        $items = PengajuanPembelian::with(['supplier', 'detail.barang'])->withCount('detail', 'penerimaan')
            ->withExists(['detail as masih_terpesan' => function ($query) {
                $query->whereRaw('pengajuan_pembelian_detail.jumlah > (SELECT COALESCE(SUM(d.jumlah), 0) FROM barang_masuk_detail d JOIN barang_masuk m ON m.id = d.id_masuk WHERE m.id_pengajuan_pembelian = pengajuan_pembelian_detail.id_pengajuan AND d.id_barang = pengajuan_pembelian_detail.id_barang)');
            }])->orderByDesc('id')->get()
            ->sortBy(fn ($item) => $item->masih_terpesan || ! $item->detail_count ? 0 : 1)->values();
        $permissions = $this->permissions();

        return view('admin.pengajuan_pembelian.index', compact('items', 'permissions'));
    }

    public function create()
    {
        $this->authorizeInventory('pengajuan-pembelian.index', 'tambah');

        return $this->form(new PengajuanPembelian(['nomor' => 'PP-'.now('Asia/Jakarta')->format('Ymd').'-'.strtoupper(\Illuminate\Support\Str::random(6)), 'tanggal' => now('Asia/Jakarta')->toDateString()]));
    }

    public function edit($id)
    {
        $this->authorizeInventory('pengajuan-pembelian.index', 'edit');
        $data = PengajuanPembelian::findOrFail($id);
        $this->ensureEditable($data);

        return $this->form($data);
    }

    private function form(PengajuanPembelian $data)
    {
        $suppliers = Supplier::orderBy('nama')->get();
        $barang = Barang::with('satuan')->orderBy('nama')->get();
        $data->load('detail');

        return view('admin.pengajuan_pembelian.form', compact('data', 'suppliers', 'barang'));
    }

    private function validated(Request $request, ?int $id = null): array
    {
        $values = $request->validate([
            'nomor' => ['required', 'string', 'max:100', Rule::unique('pengajuan_pembelian', 'nomor')->ignore($id)],
            'tanggal' => 'required|date_format:Y-m-d', 'id_supplier' => 'required|integer|exists:supplier,id',
            'keterangan' => 'nullable|string|max:5000',
            'id_barang' => 'required|array|min:1|max:200',
            'id_barang.*' => 'required|integer|distinct|exists:barang,id',
            'harga' => 'required|array|min:1|max:200',
            'harga.*' => ['required', 'regex:/^\d+(?:\.\d{3})*$/', 'max:20'],
            'jumlah' => 'required|array|min:1|max:200',
            'jumlah.*' => ['required', 'regex:/^\d+(?:\.\d{3})*$/', 'max:20'],
        ]);
        if (array_keys($values['id_barang']) !== array_keys($values['jumlah']) || array_keys($values['id_barang']) !== array_keys($values['harga'])) {
            throw ValidationException::withMessages(['jumlah' => 'Jumlah harus diisi untuk setiap barang.']);
        }
        foreach ($values['jumlah'] as $key => $quantity) {
            $number = (int) str_replace('.', '', $quantity);
            if ($number <= 0 || $number > 1000000000) {
                throw ValidationException::withMessages(['jumlah.'.$key => 'Jumlah harus antara 1 dan 1.000.000.000.']);
            }
            $values['jumlah'][$key] = $number;
            $price = (int) str_replace('.', '', $values['harga'][$key]);
            if ($price < 0 || $price > 1000000000) {
                throw ValidationException::withMessages(['harga.'.$key => 'Harga harus antara 0 dan 1.000.000.000.']);
            }
            $values['harga'][$key] = $price;
        }

        return $values;
    }

    public function store(Request $request)
    {
        $this->authorizeInventory('pengajuan-pembelian.index', 'tambah');
        $values = $this->validated($request);
        $data = DB::transaction(function () use ($values) {
            $data = PengajuanPembelian::create($values);
            $this->saveLines($data, $values);

            return $data;
        });

        return redirect()->route('pengajuan-pembelian.show', $data->id)->with('success', 'Purchase Order berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        $this->authorizeInventory('pengajuan-pembelian.index', 'edit');
        $values = $this->validated($request, (int) $id);
        DB::transaction(function () use ($id, $values) {
            $data = PengajuanPembelian::lockForUpdate()->findOrFail($id);
            $this->ensureEditable($data);
            $data->update($values);
            $data->detail()->delete();
            $this->saveLines($data, $values);
        });

        return redirect()->route('pengajuan-pembelian.show', $id)->with('success', 'Purchase Order diperbarui.');
    }

    private function saveLines(PengajuanPembelian $data, array $values): void
    {
        foreach ($values['id_barang'] as $key => $id) {
            $data->detail()->create(['id_barang' => $id, 'jumlah' => $values['jumlah'][$key], 'harga' => $values['harga'][$key]]);
        }
    }

    private function ensureEditable(PengajuanPembelian $data): void
    {
        if ($data->penerimaan()->exists()) {
            throw ValidationException::withMessages(['pengajuan' => 'Purchase Order sudah memiliki penerimaan barang dan tidak dapat diubah atau dihapus.']);
        }
    }

    public function show($id, PurchaseReceivingService $service)
    {
        $this->authorizeInventory('pengajuan-pembelian.index');
        $data = PengajuanPembelian::with(['supplier', 'penerimaan'])->findOrFail($id);
        $lines = $service->lines($data);
        $permissions = $this->permissions();

        if (request()->ajax()) {
            return view('admin.pengajuan_pembelian.detail', compact('data', 'lines', 'permissions'));
        }

        return view('admin.pengajuan_pembelian.show', compact('data', 'lines', 'permissions'));
    }

    public function pdf($id, PurchaseReceivingService $service)
    {
        $this->authorizeInventory('pengajuan-pembelian.index');
        $data = PengajuanPembelian::with('supplier')->findOrFail($id);
        $lines = $service->lines($data);

        $logoData = null;
        $logoMedia = \App\Models\PengaturanMedia::where('jenis_data', 'Logo Login')->first();
        if ($logoMedia?->nama_file) {
            $logoPath = public_path('config_media/'.$logoMedia->nama_file);
            if (is_file($logoPath) && ($info = @getimagesize($logoPath))
                && $info[0] * $info[1] <= 4000000) {
                $logoData = 'data:'.$info['mime'].';base64,'.base64_encode(file_get_contents($logoPath));
            }
        }

        return Pdf::loadView('admin.pengajuan_pembelian.pdf', compact('data', 'lines', 'logoData'))
            ->setPaper('a4', 'portrait')->stream('purchase-order-'.$data->id.'.pdf');
    }

    public function destroy($id)
    {
        $this->authorizeInventory('pengajuan-pembelian.index', 'hapus');
        DB::transaction(function () use ($id) {
            $data = PengajuanPembelian::lockForUpdate()->findOrFail($id);
            $this->ensureEditable($data);
            $data->detail()->delete();
            $data->delete();
        });

        return redirect()->route('pengajuan-pembelian.index')->with('success', 'Purchase Order dihapus.');
    }

    public function receiving($id, PurchaseReceivingService $service)
    {
        $this->authorizeInventory('barang-masuk.index', 'tambah');
        $data = PengajuanPembelian::findOrFail($id);

        return response()->json(['id' => $data->id, 'nomor' => $data->nomor,
            'id_supplier' => $data->id_supplier, 'tanggal' => $data->tanggal, 'items' => $service->lines($data)]);
    }
}
