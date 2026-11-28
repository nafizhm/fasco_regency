<?php

namespace App\Http\Controllers;

use App\Models\InvoiceIpl;
use App\Models\PengaturanMedia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvoiceIplController extends Controller
{
    use \App\Traits\InventoryAccess;

    private function permissions(): array
    {
        return (array) DB::table('hak_akses')->join('menu', 'menu.id', '=', 'hak_akses.id_menu')->where('id_user', auth()->id())->where('route_name', 'invoice-ipl.index')->select('hak_akses.*')->first();
    }

    public function index()
    {
        $this->authorizeInventory('invoice-ipl.index');

        return view('admin.invoice_ipl.index', ['items' => InvoiceIpl::with('pembayaran')->orderByDesc('id')->get(), 'permissions' => $this->permissions()]);
    }

    public function create()
    {
        $this->authorizeInventory('invoice-ipl.index', 'tambah');
        $data = new InvoiceIpl(['nomor' => 'IPL-'.now('Asia/Jakarta')->format('Ymd').'-'.strtoupper(\Illuminate\Support\Str::random(6)),
            'tanggal_stu' => now('Asia/Jakarta')->toDateString(), 'jatuh_tempo' => now('Asia/Jakarta')->addYear()->toDateString(),
            'nominal_tahun' => 0, 'jabatan' => 'PT. Dwi Raka Graha']);

        return view('admin.invoice_ipl.form', compact('data'));
    }

    public function edit($id)
    {
        $this->authorizeInventory('invoice-ipl.index', 'edit');

        return view('admin.invoice_ipl.form', ['data' => InvoiceIpl::with('pembayaran')->findOrFail($id)]);
    }

    public function show($id)
    {
        $this->authorizeInventory('invoice-ipl.index');

        return view('admin.invoice_ipl.show', ['data' => InvoiceIpl::with('pembayaran')->findOrFail($id), 'permissions' => $this->permissions()]);
    }

    public function store(Request $request)
    {
        return $this->save($request);
    }

    public function update(Request $request, $id)
    {
        return $this->save($request, $id);
    }

    private function save(Request $request, $id = null)
    {
        $this->authorizeInventory('invoice-ipl.index', $id ? 'edit' : 'tambah');
        $money = ['required', 'regex:/^[0-9]+$/', 'numeric', 'min:0', 'max:999999999999'];
        $values = $request->validate([
            'nomor' => ['required', 'string', 'max:100', Rule::unique('invoice_ipl')->ignore($id)],
            'nama' => 'required|string|max:255', 'alamat' => 'required|string|max:3000',
            'tanggal_stu' => 'required|date_format:Y-m-d', 'jatuh_tempo' => 'required|date_format:Y-m-d|after_or_equal:tanggal_stu',
            'nominal_tahun' => $money,
            'pembayaran' => 'nullable|array|max:100', 'pembayaran.*.tanggal' => 'required|date_format:Y-m-d',
            'pembayaran.*.nominal' => $money, 'pembayaran.*.transaksi' => ['required', Rule::in(['Transfer', 'Tunai', 'QRIS', 'Debit', 'Lainnya'])],
            'pembayaran.*.keterangan' => 'nullable|string|max:1000',
        ]);
        DB::transaction(function () use ($id, $values) {
            $data = $id ? InvoiceIpl::lockForUpdate()->findOrFail($id) : new InvoiceIpl;
            $data->fill(array_merge(collect($values)->except('pembayaran')->all(), InvoiceIpl::PERUSAHAAN))->save();
            $data->pembayaran()->delete();
            foreach ($values['pembayaran'] ?? [] as $line) {
                $data->pembayaran()->create($line);
            }
        });

        return redirect()->route('invoice-ipl.index')->with('success', 'Invoice IPL berhasil disimpan.');
    }

    public function destroy($id)
    {
        $this->authorizeInventory('invoice-ipl.index', 'hapus');
        DB::transaction(function () use ($id) {
            $data = InvoiceIpl::lockForUpdate()->findOrFail($id);
            $data->pembayaran()->delete();
            $data->delete();
        });

        return redirect()->route('invoice-ipl.index')->with('success', 'Invoice IPL berhasil dihapus.');
    }

    public function pdf($id)
    {
        $this->authorizeInventory('invoice-ipl.index');
        $data = InvoiceIpl::with('pembayaran')->findOrFail($id);
        $logoData = null;
        $file = PengaturanMedia::where('jenis_data', 'Logo Login')->value('nama_file');
        if ($file) {
            $path = public_path('config_media/'.$file);
            if (is_file($path) && ($info = @getimagesize($path)) && $info[0] * $info[1] <= 4000000) {
                $logoData = 'data:'.$info['mime'].';base64,'.base64_encode(file_get_contents($path));
            }
        }

        return Pdf::loadView('admin.invoice_ipl.pdf', compact('data', 'logoData'))->setPaper('a4', 'portrait')->stream('invoice-ipl-'.$data->id.'.pdf');
    }
}
