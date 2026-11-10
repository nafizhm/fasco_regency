<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\Barang;
use App\Models\BarangMasuk;
use App\Models\BarangMasukDetail;
use App\Models\BarangMasukPembayaran;
use App\Models\Supplier;
use App\Services\HargaBarangService;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class BarangMasukController extends Controller
{
    use \App\Traits\InventoryAccess;
    private const LAMPIRAN_DIR = 'assets/barang-masuk/lampiran';
    private const BUKTI_BAYAR_DIR = 'assets/barang-masuk/pembayaran';

    public function index(Request $request)
    {
        $this->authorizeInventory('barang-masuk.index', 'lihat');
        Carbon::setLocale('id');

        $permissions = HakAksesController::getUserPermissions();

        if ($request->ajax()) {
            $data = BarangMasuk::with('lampiran')->orderBy('id', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('tanggal', function ($row) {
                    return Carbon::parse($row->tanggal)->translatedFormat('d F Y');
                })
                ->addColumn('supplier', function ($row) {
                    return Supplier::find($row->id_supplier)?->nama ?? 'Supplier Tidak Diketahui';
                })
                ->addColumn('jum_item', function ($row) {
                    return number_format((int) $row->jum_item, 0, ',', '.');
                })
                ->addColumn('total_harga', function ($row) {
                    return '<div class="d-flex justify-content-between"><span>Rp.</span><span>' . number_format((int) $row->total_harga, 0, ',', '.') . '</span></div>';
                })
                ->addColumn('status_pembayaran', function ($row) {
                    if ((int) $row->terbayar <= 0) {
                        return '<span class="badge badge-danger">Belum Dibayar</span>';
                    }

                    if ((int) $row->terbayar < (int) $row->total_harga) {
                        return '<span class="badge badge-warning">Belum Lunas</span>';
                    }

                    return '<span class="badge badge-primary">Lunas</span>';
                })
                ->addColumn('action', function ($row) use ($permissions) {
                    $showUrl = route('barang-masuk.show', $row->id);
                    $deleteUrl = route('barang-masuk.destroy', $row->id);
                    $paymentUrl = route('barang-masuk.payment.store', $row->id);
                    $isLunas = (int) $row->terbayar >= (int) $row->total_harga && (int) $row->total_harga > 0;

                    $btn = '<div class="d-flex justify-content-center">';

                    if ($permissions['edit']) {
                        if ($isLunas) {
                            $btn .= '<a href="' . e($showUrl) . '" class="btn btn-secondary btn-sm mx-1">View</a>';
                        } else {
                            $btn .= '<a href="' . e($showUrl) . '" class="btn btn-primary btn-sm mx-1">Edit</a>';
                            $btn .= '<button type="button" class="btn btn-success btn-sm mx-1 btn-open-payment"'
                                . ' data-id="' . e($row->id) . '"'
                                . ' data-payment-url="' . e($paymentUrl) . '"'
                                . ' data-supplier="' . e(Supplier::find($row->id_supplier)?->nama ?? 'Supplier Tidak Diketahui') . '"'
                                . ' data-total="' . e((string) $row->total_harga) . '"'
                                . ' data-paid="' . e((string) $row->terbayar) . '"'
                                . '>Pembayaran</button>';
                        }
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
                ->rawColumns(['action', 'total_harga', 'status_pembayaran'])
                ->make(true);
        }

        return view('admin.barang_masuk.index', compact('permissions'));
    }

    public function create()
    {
        $this->authorizeInventory('barang-masuk.index', 'tambah');
        $supplierList = Supplier::orderBy('nama')->get();
        $barangList = Barang::with('satuan')->orderBy('nama')->get();

        return view('admin.barang_masuk.create', compact('supplierList', 'barangList'));
    }

    public function validateJumlah(Request $request)
    {
        $this->authorizeInventory('barang-masuk.index', 'lihat');
        return response()->json(['valid' => true]);
    }

    public function show(string $id)
    {
        $this->authorizeInventory('barang-masuk.index', 'lihat');
        $data = BarangMasuk::with(['lampiran', 'pembayaran'])->findOrFail($id);
        $detailList = BarangMasukDetail::where('id_masuk', $data->id)->orderBy('id')->get();
        $supplierList = Supplier::orderBy('nama')->get();
        $barangList = Barang::with('satuan')->orderBy('nama')->get();
        $selectedSupplierId = $data->id_supplier;
        $legacyAttachment = null;

        return view('admin.barang_masuk.show', compact(
            'data',
            'detailList',
            'supplierList',
            'barangList',
            'selectedSupplierId',
            'legacyAttachment'
        ));
    }

    public function nota(string $id)
    {
        $this->authorizeInventory('barang-masuk.index', 'lihat');
        Carbon::setLocale('id');

        $data = BarangMasuk::with(['detail.barang', 'supplier'])->findOrFail($id);
        $detailList = $data->detail;
        $pembayaranList = BarangMasukPembayaran::where('id_barang_masuk', $data->id)->orderBy('tanggal_pembayaran')->get();

        $company = app(\App\Services\InventoryCompany::class)->details();

        $nota = [
            'no_nota' => 'BM-' . str_pad((string) $data->id, 5, '0', STR_PAD_LEFT),
            'tanggal' => Carbon::parse($data->tanggal)->translatedFormat('d F Y'),
            'supplier' => $data->supplier?->nama ?? '-',
            'nama_penerima' => $data->nama_penerima ?: '-',
            'total_harga' => (int) $data->total_harga,
            'terbayar' => (int) $data->terbayar,
            'sisa' => max((int) $data->total_harga - (int) $data->terbayar, 0),
            'terbilang' => $this->terbilang((int) $data->total_harga),
            'status_pembayaran' => $this->labelStatusPembayaran($data),
        ];

        return view('admin.barang_masuk.nota', compact('company', 'nota', 'detailList', 'pembayaranList'));
    }

    private function labelStatusPembayaran(BarangMasuk $data): string
    {
        if ((int) $data->terbayar <= 0) {
            return 'Belum Dibayar';
        }

        if ((int) $data->terbayar < (int) $data->total_harga) {
            return 'Belum Lunas';
        }

        return 'Lunas';
    }

    private function terbilang(int $nilai): string
    {
        $angka = [
            '', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan',
            'sepuluh', 'sebelas',
        ];

        $convert = function (int $n) use (&$convert, $angka): string {
            if ($n < 12) {
                return $angka[$n];
            }
            if ($n < 20) {
                return $convert($n - 10) . ' belas';
            }
            if ($n < 100) {
                return $convert((int) floor($n / 10)) . ' puluh ' . $angka[$n % 10];
            }
            if ($n < 200) {
                return 'seratus ' . $convert($n - 100);
            }
            if ($n < 1000) {
                return $convert((int) floor($n / 100)) . ' ratus ' . $convert($n % 100);
            }
            if ($n < 2000) {
                return 'seribu ' . $convert($n - 1000);
            }
            if ($n < 1000000) {
                return $convert((int) floor($n / 1000)) . ' ribu ' . $convert($n % 1000);
            }
            if ($n < 1000000000) {
                return $convert((int) floor($n / 1000000)) . ' juta ' . $convert($n % 1000000);
            }

            return $convert((int) floor($n / 1000000000)) . ' miliar ' . $convert($n % 1000000000);
        };

        return trim($convert($nilai)) ?: 'nol';
    }

    public function store(Request $request)
    {
        $this->authorizeInventory('barang-masuk.index', 'tambah');
        $request->validate($this->rules(), $this->messages());
        $this->validateItems($request);

        DB::beginTransaction();
        $storedFiles = [];
        try {
            $summary = $this->buildSummary($request);

            $data = BarangMasuk::create([
                'id_po' => null,
                'id_supplier' => $request->id_supplier,
                'tanggal' => $request->tanggal,
                'nama_penerima' => $request->nama_penerima,
                'total_harga' => $summary['total_harga'],
                'jum_item' => $summary['jum_item'],
                'terbayar' => 0,
                'status' => 0,
            ]);

            $this->syncDetail($data, $request);
            $data->load('detail');

            app(StockService::class)->addBarangMasuk($data);
            app(HargaBarangService::class)->handleCreate($data);
            $storedFiles = $this->storeLampiran($data, $request);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Barang Masuk berhasil disimpan.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->purgeLampiranFiles($storedFiles);
            if ($e instanceof \Illuminate\Validation\ValidationException) { throw $e; }
            Log::error('Error saving Barang Masuk: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan Barang Masuk.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $this->authorizeInventory('barang-masuk.index', 'edit');
        $request->validate($this->rules(), $this->messages());
        $this->validateItems($request);

        DB::beginTransaction();
        $storedFiles = [];
        $deletedFiles = [];
        try {
            $data = BarangMasuk::with(['lampiran', 'detail'])->lockForUpdate()->findOrFail($id);
            $oldDetails = $data->detail->map(fn ($detail) => clone $detail);
            if ((int) $data->terbayar >= (int) $data->total_harga && (int) $data->total_harga > 0) {
                throw \Illuminate\Validation\ValidationException::withMessages(['pembayaran' => 'Data yang sudah lunas tidak bisa diedit lagi.']);
            }
            $summary = $this->buildSummary($request);

            if ($summary['total_harga'] < (int) $data->terbayar) {
                throw \Illuminate\Validation\ValidationException::withMessages(['harga_beli' => 'Total pembelian tidak boleh lebih kecil dari pembayaran yang sudah tercatat.']);
            }
            $data->update([
                'id_supplier' => $request->id_supplier,
                'tanggal' => $request->tanggal,
                'nama_penerima' => $request->nama_penerima,
                'id_po' => null,
                'total_harga' => $summary['total_harga'],
                'jum_item' => $summary['jum_item'],
            ]);

            BarangMasukDetail::where('id_masuk', $data->id)->delete();
            $this->syncDetail($data, $request);
            $deletedFiles = $this->deleteMarkedLampiran($data, $request);
            $data->load('detail');

            $this->refreshPaymentStatus($data);

            app(StockService::class)->updateBarangMasuk($data);
            app(HargaBarangService::class)->handleUpdate($data->fresh('detail'), $oldDetails);
            $storedFiles = $this->storeLampiran($data, $request);

            DB::commit();
            $this->purgeLampiranFiles($deletedFiles);

            return response()->json([
                'status' => 'success',
                'message' => 'Barang Masuk berhasil disimpan.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->purgeLampiranFiles($storedFiles);
            if ($e instanceof \Illuminate\Validation\ValidationException) { throw $e; }
            Log::error('Error updating Barang Masuk: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan Barang Masuk.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        $this->authorizeInventory('barang-masuk.index', 'hapus');
        DB::beginTransaction();
        try {
            $data = BarangMasuk::with(['lampiran', 'pembayaran', 'detail'])->lockForUpdate()->findOrFail($id);
            if ($data->pembayaran->isNotEmpty()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['pembayaran' => 'Pembelian yang sudah memiliki pembayaran tidak dapat dihapus.']);
            }
            $deletedDetails = $data->detail->map(fn ($detail) => clone $detail);
            $deletedFiles = $data->lampiran->pluck('file_name')->filter()->values()->all();
            $deletedProofFiles = $data->pembayaran->pluck('bukti_bayar')->filter()->values()->all();
            app(StockService::class)->deleteBarangMasuk($data);

            $data->lampiran()->delete();
            $data->pembayaran()->delete();
            BarangMasukDetail::where('id_masuk', $data->id)->delete();
            $data->delete();
            app(HargaBarangService::class)->handleDelete($data, $deletedDetails);

            DB::commit();
            $this->purgeLampiranFiles($deletedFiles);
            $this->purgePaymentFiles($deletedProofFiles);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Illuminate\Validation\ValidationException) { throw $e; }
            Log::error('Error destroying Barang Masuk: ' . $e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Gagal menghapus Barang Masuk.'], 500);
        }
    }

    public function paymentInfo(string $id)
    {
        $this->authorizeInventory('barang-masuk.index', 'lihat');
        $data = BarangMasuk::with('pembayaran')->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $data->id,
                'tanggal' => $data->tanggal,
                'total_harga' => (int) $data->total_harga,
                'terbayar' => (int) $data->terbayar,
                'sisa' => max((int) $data->total_harga - (int) $data->terbayar, 0),
                'jum_item' => (int) $data->jum_item,
                'status' => $this->getPaymentStatusLabel($data),
                'pembayaran' => $data->pembayaran
                    ->sortByDesc('id')
                    ->values()
                    ->map(function ($pembayaran) {
                        return [
                            'tanggal_pembayaran' => Carbon::parse($pembayaran->tanggal_pembayaran)->translatedFormat('d F Y'),
                            'jumlah_bayar' => number_format((int) $pembayaran->jumlah_bayar, 0, ',', '.'),
                            'keterangan' => $pembayaran->keterangan ?: '-',
                            'bukti_url' => $pembayaran->bukti_bayar ? asset(self::BUKTI_BAYAR_DIR . '/' . $pembayaran->bukti_bayar) : null,
                            'bukti_ext' => strtolower($pembayaran->bukti_bayar_extension ?: ''),
                            'bukti_type' => strtolower($pembayaran->bukti_bayar_extension ?: '') === 'pdf' ? 'pdf' : 'image',
                            'bukti_name' => $pembayaran->bukti_bayar_original_name ?: $pembayaran->bukti_bayar,
                        ];
                    }),
            ],
        ]);
    }

    public function storePayment(Request $request, string $id)
    {
        $this->authorizeInventory('barang-masuk.index', 'edit');
        $data = BarangMasuk::findOrFail($id);

        $request->validate([
            'tanggal_pembayaran' => 'required|date',
            'jumlah_bayar' => ['required', 'regex:/^\d+(?:\.\d{3})*$/', 'max:20'],
            'keterangan_pembayaran' => 'nullable|string',
            'bukti_bayar' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ], [
            'tanggal_pembayaran.required' => 'Tanggal pembayaran wajib diisi.',
            'jumlah_bayar.required' => 'Jumlah bayar wajib diisi.',
            'bukti_bayar.mimes' => 'Bukti bayar harus berupa JPG, JPEG, PNG, atau PDF.',
            'bukti_bayar.max' => 'Ukuran bukti bayar maksimal 2MB.',
        ]);

        $jumlahBayar = (int) str_replace('.', '', (string) $request->jumlah_bayar);
        DB::beginTransaction();
        $storedFile = null;
        try {
            $data = BarangMasuk::lockForUpdate()->findOrFail($id);
            $sisaBayar = max((int) $data->total_harga - (int) $data->terbayar, 0);
            if ($jumlahBayar <= 0 || $jumlahBayar > $sisaBayar) {
                throw \Illuminate\Validation\ValidationException::withMessages(['jumlah_bayar' => 'Jumlah bayar harus lebih besar dari nol dan tidak melebihi sisa bayar.']);
            }
            $filePayload = $this->storePaymentProof($request);
            $storedFile = $filePayload['bukti_bayar'] ?? null;

            BarangMasukPembayaran::create([
                'id_barang_masuk' => $data->id,
                'tanggal_pembayaran' => $request->tanggal_pembayaran,
                'jumlah_bayar' => $jumlahBayar,
                'keterangan' => $request->keterangan_pembayaran ?? '',
                'bukti_bayar' => $filePayload['bukti_bayar'] ?? null,
                'bukti_bayar_original_name' => $filePayload['bukti_bayar_original_name'] ?? null,
                'bukti_bayar_extension' => $filePayload['bukti_bayar_extension'] ?? null,
            ]);

            $this->refreshPaymentStatus($data->fresh());

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pembayaran berhasil disimpan.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->deletePaymentFile($storedFile);
            if ($e instanceof \Illuminate\Validation\ValidationException) { throw $e; }
            Log::error('Error saving Barang Masuk payment: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan pembayaran.',
            ], 500);
        }
    }

    private function rules(): array
    {
        return [
            'id_supplier' => 'required|exists:supplier,id',
            'nama_penerima' => 'required|string|max:50',
            'tanggal' => 'required|date',
            'id_barang' => 'required|array|min:1',
            'id_barang.*' => 'required|distinct|exists:barang,id',
            'jumlah' => 'required|array|min:1',
            'jumlah.*' => ['required', 'regex:/^\d+(?:\.\d{3})*$/', 'max:20'],
            'harga_beli' => 'required|array|min:1',
            'harga_beli.*' => ['required', 'regex:/^\d+(?:\.\d{3})*$/', 'max:20'],
            'keterangan' => 'nullable|array',
            'keterangan.*' => 'nullable|string|max:1000',
            'lampiran' => 'nullable|array',
            'lampiran.*' => 'file|mimes:jpg,jpeg,png,pdf|max:2048',
            'lampiran_nota' => 'nullable|array|max:10',
            'lampiran_nota.*' => 'file|mimes:jpg,jpeg,png,pdf|max:2048',
            'lampiran_surat_jalan' => 'nullable|array|max:10',
            'lampiran_surat_jalan.*' => 'file|mimes:jpg,jpeg,png,pdf|max:2048',
            'lampiran_foto_barang' => 'nullable|array|max:10',
            'lampiran_foto_barang.*' => 'file|mimes:jpg,jpeg,png,webp|max:2048',
            'delete_attachment_ids' => 'nullable|array',
            'delete_attachment_ids.*' => 'integer',
        ];
    }

    private function validateItems(Request $request): void
    {
        $keys = array_keys($request->id_barang);
        if ($keys !== array_keys($request->jumlah) || $keys !== array_keys($request->harga_beli)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['jumlah' => 'Setiap barang harus memiliki jumlah dan harga beli.']);
        }
        foreach ($keys as $key) {
            $quantity = (int) str_replace('.', '', $request->jumlah[$key]);
            $price = (int) str_replace('.', '', $request->harga_beli[$key]);
            if ($quantity <= 0 || $quantity > 1000000000 || $price > 1000000000000 || $quantity * $price > 9000000000000000) {
                throw \Illuminate\Validation\ValidationException::withMessages(['jumlah.'.$key => 'Jumlah harus positif dan nominal pembelian dalam batas yang valid.']);
            }
        }
    }

    private function messages(): array
    {
        return [
            'id_supplier.required' => 'Supplier wajib dipilih.',
            'id_supplier.exists' => 'Supplier tidak ditemukan.',
            'nama_penerima.required' => 'Nama penerima harus diisi.',
            'tanggal.required' => 'Tanggal masuk harus diisi.',
            'id_barang.required' => 'Minimal 1 barang harus diisi.',
            'id_barang.*.required' => 'Barang harus dipilih untuk setiap baris.',
            'id_barang.*.distinct' => 'Barang tidak boleh dipilih lebih dari satu kali.',
            'id_barang.*.exists' => 'Barang yang dipilih tidak valid.',
            'jumlah.required' => 'Jumlah harus diisi.',
            'jumlah.*.required' => 'Jumlah harus diisi untuk setiap barang.',
            'harga_beli.required' => 'Harga beli harus diisi.',
            'harga_beli.*.required' => 'Harga beli harus diisi untuk setiap barang.',
            'lampiran.*.mimes' => 'Lampiran harus berupa JPG, JPEG, PNG, atau PDF.',
            'lampiran.*.max' => 'Ukuran setiap lampiran maksimal 2MB.',
            'lampiran_nota.max' => 'Maksimal 10 file nota pembelian.',
            'lampiran_surat_jalan.max' => 'Maksimal 10 file surat jalan.',
            'lampiran_foto_barang.max' => 'Maksimal 10 foto barang datang.',
            'lampiran_nota.*.mimes' => 'Nota harus berupa JPG, PNG, atau PDF.',
            'lampiran_surat_jalan.*.mimes' => 'Surat jalan harus berupa JPG, PNG, atau PDF.',
            'lampiran_foto_barang.*.mimes' => 'Foto barang datang harus berupa JPG, PNG, atau WEBP.',
            'lampiran_nota.*.max' => 'Ukuran nota maksimal 2 MB per file.',
            'lampiran_surat_jalan.*.max' => 'Ukuran surat jalan maksimal 2 MB per file.',
            'lampiran_foto_barang.*.max' => 'Ukuran foto maksimal 2 MB per file.',

        ];
    }

    private function syncDetail(BarangMasuk $data, Request $request): void
    {
        foreach ($request->id_barang as $index => $idBarang) {
            $jumlah = (int) str_replace('.', '', $request->jumlah[$index]);
            $hargaBeli = (int) str_replace('.', '', $request->harga_beli[$index]);

            BarangMasukDetail::create([
                'id_masuk' => $data->id,
                'id_barang' => $idBarang,
                'jumlah' => $jumlah,
                'keterangan' => $request->keterangan[$index] ?? '',
                'harga_beli' => $hargaBeli,
                'sub_total' => $hargaBeli * $jumlah,
            ]);
        }
    }

    private function buildSummary(Request $request): array
    {
        $totalHarga = 0;

        foreach ($request->id_barang as $index => $idBarang) {
            $jumlah = (int) str_replace('.', '', $request->jumlah[$index]);
            $hargaBeli = (int) str_replace('.', '', $request->harga_beli[$index]);

            $totalHarga += ($jumlah * $hargaBeli);
        }

        return [
            'total_harga' => $totalHarga,
            'jum_item' => count($request->id_barang),
        ];
    }

    private function storeLampiran(BarangMasuk $data, Request $request): array
    {
        $groups = ['lampiran' => null, 'lampiran_nota' => 'nota',
            'lampiran_surat_jalan' => 'surat_jalan', 'lampiran_foto_barang' => 'foto_barang'];
        $storedFiles = [];
        try {
            foreach ($groups as $field => $category) {
                foreach ($request->file($field, []) as $file) {
                    $directory = public_path(self::LAMPIRAN_DIR);
                    if (!is_dir($directory)) {
                        mkdir($directory, 0755, true);
                    }
                    $extension = strtolower($file->extension());
                    $fileName = Str::uuid() . '.' . $extension;
                    $file->move($directory, $fileName);
                    $storedFiles[] = $fileName;
                    $data->lampiran()->create([
                        'file_name' => $fileName,
                        'file_original_name' => $file->getClientOriginalName(),
                        'file_extension' => $extension,
                        'jenis_lampiran' => $category,
                    ]);
                }
            }
        } catch (\Throwable $error) {
            $this->purgeLampiranFiles($storedFiles);
            throw $error;
        }
        return $storedFiles;
    }

    private function deleteMarkedLampiran(BarangMasuk $data, Request $request): array
    {
        $deleteIds = collect($request->input('delete_attachment_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($deleteIds->isEmpty()) {
            return [];
        }

        $lampiranList = $data->lampiran()->whereIn('id', $deleteIds)->get();
        $deletedFiles = [];

        foreach ($lampiranList as $lampiran) {
            if ($lampiran->file_name) {
                $deletedFiles[] = $lampiran->file_name;
            }
            $lampiran->delete();
        }

        return $deletedFiles;
    }

    private function storePaymentProof(Request $request): array
    {
        if (!$request->hasFile('bukti_bayar') || !$request->file('bukti_bayar')->isValid()) {
            return [];
        }

        $directory = public_path(self::BUKTI_BAYAR_DIR);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $file = $request->file('bukti_bayar');
        $extension = strtolower($file->getClientOriginalExtension());
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($directory, $fileName);

        return [
            'bukti_bayar' => $fileName,
            'bukti_bayar_original_name' => $file->getClientOriginalName(),
            'bukti_bayar_extension' => $extension,
        ];
    }

    private function refreshPaymentStatus(BarangMasuk $data): void
    {
        $terbayar = (int) $data->pembayaran()->sum('jumlah_bayar');
        $totalHarga = (int) $data->total_harga;

        $data->update([
            'terbayar' => $terbayar,
            'status' => $totalHarga > 0 && $terbayar >= $totalHarga ? 1 : 0,
        ]);
    }

    private function getPaymentStatusLabel(BarangMasuk $data): string
    {
        $terbayar = (int) $data->terbayar;
        $totalHarga = (int) $data->total_harga;

        if ($terbayar <= 0) {
            return 'Belum Dibayar';
        }

        if ($terbayar < $totalHarga) {
            return 'Belum Lunas';
        }

        return 'Lunas';
    }

    private function purgeLampiranFiles(array $fileNames): void
    {
        foreach ($fileNames as $fileName) {
            $this->deleteLampiranFile($fileName);
        }
    }

    private function purgePaymentFiles(array $fileNames): void
    {
        foreach ($fileNames as $fileName) {
            $this->deletePaymentFile($fileName);
        }
    }

    private function deleteLampiranFile(?string $fileName): void
    {
        if (!$fileName) {
            return;
        }

        $path = public_path(self::LAMPIRAN_DIR . '/' . $fileName);

        if (file_exists($path)) {
            unlink($path);
        }
    }

    private function deletePaymentFile(?string $fileName): void
    {
        if (!$fileName) {
            return;
        }

        $path = public_path(self::BUKTI_BAYAR_DIR . '/' . $fileName);

        if (file_exists($path)) {
            unlink($path);
        }
    }
}

