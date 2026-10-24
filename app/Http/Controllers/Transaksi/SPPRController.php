<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\Customer;
use App\Models\SPPR;
use App\Models\Bank;
use App\Models\Pemasukan;
use App\Models\Perusahaan;
use App\Services\DocumentDataContext;
use App\Traits\LogAktivitasTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class SPPRController extends Controller
{
    use LogAktivitasTrait;

    public function index(Request $request)
    {
        $permissions = HakAksesController::getUserPermissions();

        if ($request->ajax()) {
            $data = SPPR::with(['customer.kavling', 'customer.marketing'])->orderBy('id', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('customer_nama', function ($row) {
                    return $row->nama;
                })
                ->addColumn('lokasi_unit', function ($row) {
                    return $row->customer?->kavling?->kode_kavling ?? '-';
                })
                ->addColumn('nama_marketing', function ($row) {
                    return $row->customer?->marketing?->nama_marketing ?? '-';
                })
                ->addColumn('action', function ($row) use ($permissions) {
                    $cetakUrl = route('sppr.cetak', $row->id);
                    $editUrl = route('sppr.edit', $row->id);
                    $deleteUrl = route('sppr.destroy', $row->id);

                    $btn = '<div class="d-flex justify-content-center">';
                    if ($permissions['edit']) {
                        $btn .= '<button class="btn btn-info btn-sm mx-1 edit-button"
                                data-id="' . e($row->id) . '"
                                data-url="' . e($editUrl) . '">Edit</button>';
                    }
                    $btn .= '<a href="' . e($cetakUrl) . '" target="_blank" class="btn btn-dark btn-sm mx-1">Cetak SPR</a>';
                    if ($permissions['hapus']) {
                        $btn .= '<form action="' . e($deleteUrl) . '" method="POST" style="display:inline;">
                        ' . csrf_field() . method_field('DELETE') . '
                        <button type="submit" class="delete-button btn btn-danger btn-sm mx-1">Hapus</button>
                        </form>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $customerList = Customer::orderBy('nama_lengkap')->get();

        return view('admin.transaksi.sppr.index', compact('permissions', 'customerList'));
    }

    public function getCustomerDetail($id)
    {
        $customer = Customer::with(['lokasi', 'kavling'])->findOrFail($id);

        return response()->json(['status' => 'success', 'data' => array_merge([
            'nama' => $customer->nama_lengkap,
            'alamat' => $customer->alamat_ktp ?? $customer->alamat_domisili ?? '',
            'nik' => $customer->nik,
            'no_telp' => $customer->no_telp,
            'lokasi_unit' => $customer->kavling?->kode_kavling ?? '',
            'harga_jual' => $customer->hrg_jual ?? 0,
        ], $this->kavlingData($customer))]);
    }

    private function kavlingData(Customer $customer): array
    {
        $kavling = $customer->kavling;
        if ($customer->lokasi?->is_cluster) {
            $blok = $kavling?->nama_cluster ?? $kavling?->cluster ?? '';
            $no = $kavling?->no ?? '';
        } else {
            $parts = explode('-', $kavling?->kode_kavling ?? '', 2);
            $blok = $parts[0];
            $no = $parts[1] ?? '';
        }

        return [
            'blok' => $blok,
            'no' => $no,
            'luas_bangunan' => $kavling?->luas_bangunan ?? 0,
            'luas_tanah' => $kavling?->luas_tanah ?? 0,
        ];
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'id_customer' => 'required|integer|exists:customer,id',
            'no_sppr' => 'required|string|max:100',
            'nama' => 'required|string|max:255',
            'alamat' => 'required|string',
            'nik' => 'required|string|max:20',
            'no_telp' => 'required|string|max:20',
            'harga_jual' => 'required|integer|min:0',
            'nominal_dp' => 'required|integer|min:0',
            'asumsi_plafon_kpr' => 'required|integer|min:0',
            'penandatangan' => 'nullable|string|max:255',
        ]);
        $customer = Customer::with(['lokasi', 'kavling'])->findOrFail($data['id_customer']);
        if (!$customer->kavling) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'id_customer' => 'Customer belum memiliki data kavling. Lengkapi kavling terlebih dahulu.',
            ]);
        }

        // Ukuran dan unit selalu berasal dari menu kavling, bukan input browser.
        return array_merge($data, $this->kavlingData($customer));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $sppr = SPPR::create($data);
        $this->logCreate('SPPR', $sppr->id);

        return response()->json(['status' => 'success']);
    }

    public function edit($id)
    {
        $sppr = SPPR::with(['customer.lokasi', 'customer.kavling'])->findOrFail($id);
        $data = $sppr->toArray();
        $data['lokasi_unit'] = $sppr->customer?->kavling?->kode_kavling ?? '';
        if ($sppr->customer) {
            $data = array_merge($data, $this->kavlingData($sppr->customer));
        }

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function update(Request $request, $id)
    {
        $sppr = SPPR::findOrFail($id);
        $sppr->update($this->validatedData($request));
        $this->logEdit('SPPR', $sppr->id);

        return response()->json(['status' => 'success']);
    }

    public function destroy($id)
    {
        $sppr = SPPR::findOrFail($id);

        $this->logDelete('SPPR', $sppr->id);
        $sppr->delete();

        return response()->json(['status' => 'success']);
    }


    public function cetak($id)
    {
        $sppr = SPPR::with(['customer.lokasi.perusahaan', 'customer.kavling.perusahaan'])->findOrFail($id);
        abort_unless($sppr->customer?->kavling, 422, 'Data kavling customer belum tersedia.');
        $unit = $this->kavlingData($sppr->customer);
        $templatePath = public_path('templates/template_spr.docx');
        if (!is_file($templatePath)) {
            $templatePath = public_path('templates/template_sppr/template_sppr.docx');
        }
        abort_unless(file_exists($templatePath), 404, 'Template SPR tidak ditemukan.');
        $lokasi = $sppr->customer->lokasi;
        $kavling = $sppr->customer->kavling;
        $perusahaan = $kavling->perusahaan;
        if (! $perusahaan && $lokasi) {
            $perusahaanId = $lokasi->perusahaan->first()?->id_perusahaan;
            $perusahaan = $perusahaanId ? Perusahaan::find($perusahaanId) : null;
        }
        $history = Pemasukan::with('kategori')->where('id_customer', $sppr->id_customer)
            ->where(fn ($query) => $query->whereNull('keterangan')->orWhere('keterangan', 'NOT LIKE', 'Biaya ganti nama%'))
            ->orderBy('tanggal')->orderBy('id')->get();
        $paidDeposit = $history->reject(fn ($payment) => preg_match('/plafon|pencairan|realisasi/i',
            ($payment->keterangan_kategori ?? '') . ' ' . ($payment->kategori?->kategori ?? '') . ' ' . ($payment->keterangan ?? '')))->sum('nominal');
        $sisaDp = max(0, $sppr->nominal_dp - $paidDeposit);

        // Mengganti teks saja: tabel, tab stop, kop, dan format Word tetap dari template.
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
        $template = new TemplateProcessor($templatePath);
        $tanggal = $sppr->created_at ? Carbon::parse($sppr->created_at) : Carbon::now();
        $bulanRomawi = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $nomor = $sppr->no_sppr ?: str_pad($sppr->id, 3, '0', STR_PAD_LEFT);
        if (!str_contains($nomor, '/')) {
            $nomor .= '/SPR/' . ($lokasi?->nama_singkat ? $lokasi->nama_singkat . '/' : '') . $bulanRomawi[$tanggal->month - 1] . '/' . $tanggal->year;
        }
        $fmt = fn ($value) => number_format((int) $value, 0, ',', '.');
        $words = fn ($value) => mb_strtolower(DocumentDataContext::terbilang((int) $value));
        $alamat = preg_replace('/\s+/u', ' ', trim($sppr->alamat ?: $sppr->customer->alamat_ktp ?: $sppr->customer->alamat_domisili ?: '-'));
        $alamatLines = explode("\n", wordwrap($alamat, 55, "\n"));
        $variables = $template->getVariables();
        $template->setValues([
            'no_surat' => $nomor,
            'nama' => $sppr->nama,
            'nama_ttd' => mb_strtoupper($sppr->nama),
            'alamat' => $alamat,
            // Placeholder pada template SPR versi lama.
            'alamat_1' => $alamatLines[0] ?? '',
            'alamat_2' => $alamatLines[1] ?? '',
            'alamat_3' => implode(' ', array_slice($alamatLines, 2)),
            'blok_no' => trim($unit['blok'] . ($unit['no'] !== '' ? '/' . $unit['no'] : '')),
            'asumsi_plafon_kpr' => $fmt($sppr->asumsi_plafon_kpr),
            'nik' => $sppr->nik,
            'no_telp' => $sppr->no_telp,
            'luas_bangunan' => (string) $unit['luas_bangunan'],
            'luas_tanah' => (string) $unit['luas_tanah'],
            'kavling' => $kavling->nama_cluster
                ? ucfirst(strtolower($kavling->nama_cluster)) . ' No. ' . ($kavling->no ?: $kavling->kode_kavling)
                : $kavling->kode_kavling,
            'harga_jual' => $fmt($sppr->harga_jual),
            'nominal_dp' => $fmt($sppr->nominal_dp),
            'harga_terbilang' => $words($sppr->harga_jual),
            'dp_terbilang' => $words($sppr->nominal_dp),
            'sisa_dp' => $fmt($sisaDp),
            'sisa_dp_terbilang' => $words($sisaDp),
            'skema_pembayaran' => $sppr->customer->jenis_pembelian ?: '-',
            'nama_perumahan' => $lokasi?->nama_kavling ?: '-',
            'alamat_perumahan' => $lokasi?->alamat ?: '-',
            'nama_perusahaan' => $perusahaan?->nama_perusahaan ?: '-',
            'alamat_perusahaan' => $perusahaan?->alamat_perusahaan ?: '-',
            'telp_perusahaan' => $perusahaan?->telp_perusahaan ?: '-',
            'penandatangan' => $sppr->penandatangan ?: $perusahaan?->nama_penandatangan ?: '-',
            'nik_penjual' => $perusahaan?->form_nik ?: $perusahaan?->nik_penandatangan ?: '-',
            'alamat_penjual' => $perusahaan?->alamat_perusahaan ?: '-',
            'telp_penjual' => $perusahaan?->telp_perusahaan ?: '-',
            'kota_penandatangan' => $perusahaan?->kota_penandatangan ?: '-',
            'history_total' => 'Rp. ' . $fmt($history->sum('nominal')),
            'tanggal_surat' => $tanggal->locale('id')->isoFormat('D MMMM YYYY'),
        ]);
        $rows = $history->map(fn ($payment, $index) => [
            'history_no' => $index + 1,
            'history_tanggal' => $payment->tanggal ? Carbon::parse($payment->tanggal)->locale('id')->isoFormat('D MMMM YYYY') : '-',
            'history_keterangan' => $payment->keterangan_kategori ?: $payment->kategori?->kategori ?: $payment->keterangan ?: 'Pembayaran',
            'history_kwitansi' => $payment->no_kwitansi ?: '-',
            'history_nominal' => 'Rp. ' . $fmt($payment->nominal),
        ])->all();
        if (in_array('history_no', $variables, true)) {
            $template->cloneRowAndSetValues('history_no', $rows ?: [[
                'history_no' => '-', 'history_tanggal' => '-', 'history_keterangan' => 'Belum ada pembayaran',
                'history_kwitansi' => '-', 'history_nominal' => 'Rp. 0',
            ]]);
        }
        $banks = Bank::orderBy('id')->limit(3)->get();
        for ($i = 1; $i <= 3; $i++) {
            $bank = $banks->get($i - 1);
            $template->setValues([
                'bank_nama_' . $i => $bank?->nama ?: '-',
                'bank_rekening_' . $i => $bank?->no_rek ?: '-',
                'bank_pemilik_' . $i => $bank?->pemilik_rek ?: '-',
            ]);
        }
        $tempFile = tempnam(sys_get_temp_dir(), 'spr_');
        $template->saveAs($tempFile);

        return response()->download($tempFile, 'SPR_' . Str::slug($sppr->nama) . '.docx')
            ->deleteFileAfterSend(true);
    }
}
