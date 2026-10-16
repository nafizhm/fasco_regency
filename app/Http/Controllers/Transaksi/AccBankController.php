<?php
namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\Wawancara;
use App\Models\WawancaraSp3k;
use App\Traits\LogAktivitasTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

Carbon::setLocale('id');

class AccBankController extends Controller
{
    use LogAktivitasTrait;
    public function index(Request $request)
    {
        $permissions = HakAksesController::getUserPermissions();

        app(AkadController::class)->refreshDeadlineAkad();

        if ($request->ajax()) {
            $data = WawancaraSp3k::with(
                'wawancara.customer',
                'wawancara.customer.lokasi',
                'wawancara.customer.kavling',
                'bankKPR'
            )
                ->whereHas('wawancara.customer', function ($q) {
                    $q->where('stt_arsip', 0);
                })
                ->orderByDesc('id');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('lokasi_rumah', function ($row) {
                    $kode = $row->wawancara->customer->kavling->kode_kavling ?? '-';
                    $nama = $row->wawancara->customer->lokasi->nama_kavling ?? '-';
                    return "$kode - $nama";
                })
                ->addColumn('bankKPR', function ($row) {
                    return optional($row->bankKPR)->nama ?? '-';
                })
                ->editColumn('harga_jual', function ($row) {
                    return '
                    <div class="d-flex justify-content-between harga-format w-100">
                        <span>Rp.</span>
                        <span>' . number_format($row->wawancara->customer->kavling->hrg_jual, 0, ',', '.') . '</span>
                    </div>';
                })
                ->editColumn('acc_plafon', function ($row) {
                    return '
                    <div class="d-flex justify-content-between harga-format w-100">
                        <span>Rp.</span>
                        <span>' . number_format($row->acc_plafon, 0, ',', '.') . '</span>
                    </div>';
                })
                ->addColumn('dp_nilai', fn ($row) => $row->dp_nilai === null ? '-' : number_format($row->dp_nilai, 0, ',', '.'))
                ->editColumn('tgl_terbit_sp3k', function ($row) {
                    return $row->tgl_terbit_sp3k ? Carbon::parse($row->tgl_terbit_sp3k)->translatedFormat('d F Y') : '-';
                })

                ->editColumn('tgl_expired', function ($row) {
                    return $row->tgl_expired ? Carbon::parse($row->tgl_expired)->translatedFormat('d F Y') : '-';
                })

                ->addColumn('sisa_hari', function ($row) {
                    if ($row->tgl_terbit_sp3k && $row->tgl_expired) {
                        try {
                            $tglExpired = Carbon::parse($row->tgl_expired)->startOfDay();
                            $today      = Carbon::now()->startOfDay();

                            $sisaHari = $today->diffInDays($tglExpired, false);

                            return $sisaHari > 0
                                ? $sisaHari . ' hari'
                                : '<span class="text-danger">Kadaluarsa</span>';
                        } catch (\Exception $e) {
                            return '<span class="text-danger">Tanggal tidak valid</span>';
                        }
                    }

                    return '-';
                })
                ->addColumn('action', function ($row) use ($permissions) {
                    $editUrl   = route('acc-bank.show', $row->id_wawancara);
                    $deleteUrl = route('acc-bank.destroy', $row->id);

                    $btn = '<div class="d-flex justify-content-center">';
                    if ($permissions['edit']) {
                        $btn .= '<a href="' . e($editUrl) . '" class="btn btn-primary btn-sm mx-1">Detail</a>';
                        $btn .= '<a href="' . e(route('acc-bank.edit', $row->id)) . '" class="btn btn-info btn-sm mx-1">Edit</a>';
                    }

                    if ($permissions['hapus']) {
                        $btn .= '<form action="' . e($deleteUrl) . '" method="POST" style="display:inline;">' . csrf_field() . method_field('DELETE') . '<button type="submit" class="delete-button btn btn-danger btn-sm">Batalkan</button></form>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })

                ->rawColumns(['action', 'harga_jual', 'acc_plafon', 'sisa_hari'])
                ->make(true);
        }

        return view('admin.transaksi.acc_bank.index', compact('permissions'));
    }

    public function show(Request $request, $id)
    {
        $data = Wawancara::with('customer', 'customer.lokasi', 'customer.kavling')->findOrFail($id);

        if ($request->ajax()) {
            $data = WawancaraSp3k::with('wawancara.customer', 'wawancara.customer.lokasi', 'wawancara.customer.kavling', 'wawancara.bankKPR')
                ->where('id_wawancara', $id)->orderBy('id', 'asc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('dp_nilai', fn ($row) => $row->dp_nilai === null ? '-' : number_format($row->dp_nilai, 0, ',', '.'))
                ->addColumn('action', function ($row) {
                    return $this->canEditSp3k() ? '<a class="btn btn-info btn-sm" href="' . e(route('acc-bank.edit', $row->id)) . '">Edit</a>' : '-';
                })
                ->addColumn('bankKPR', function ($row) {
                    return optional($row->bankKPR)->nama ?? '-';
                })
                ->editColumn('acc_plafon', function ($row) {
                    return '
                    <div class="d-flex justify-content-between harga-format w-100">
                        <span>Rp.</span>
                        <span>' . number_format($row->acc_plafon, 0, ',', '.') . '</span>
                    </div>';
                })
                ->editColumn('tgl_terbit_sp3k', function ($row) {
                    return $row->tgl_terbit_sp3k ? Carbon::parse($row->tgl_terbit_sp3k)->translatedFormat('d F Y') : '-';
                })

                ->editColumn('tgl_expired', function ($row) {
                    return $row->tgl_expired ? Carbon::parse($row->tgl_expired)->translatedFormat('d F Y') : '-';
                })

                ->addColumn('sisa_hari', function ($row) {
                    if ($row->tgl_terbit_sp3k && $row->tgl_expired) {
                        try {
                            $tglExpired = Carbon::parse($row->tgl_expired)->startOfDay();
                            $today      = Carbon::now()->startOfDay();

                            $sisaHari = $today->diffInDays($tglExpired, false);

                            return $sisaHari > 0
                                ? $sisaHari . ' hari'
                                : '<span class="text-danger">Kadaluarsa</span>';
                        } catch (\Exception $e) {
                            return '<span class="text-danger">Tanggal tidak valid</span>';
                        }
                    }

                    return '-';
                })
                ->rawColumns(['acc_plafon', 'sisa_hari', 'action'])
                ->make(true);
        }

        return view('admin.transaksi.acc_bank.detail', compact('data'));
    }

    private function canEditSp3k(): bool
    {
        return DB::table('hak_akses')->join('menu', 'menu.id', '=', 'hak_akses.id_menu')
            ->where('hak_akses.id_user', \Illuminate\Support\Facades\Auth::id())
            ->where('menu.route_name', 'acc-bank.index')->where('hak_akses.lihat', 1)->where('hak_akses.edit', 1)->exists();
    }

    private function authorizeSp3kEdit(): void
    {
        abort_unless($this->canEditSp3k(), 403, 'Anda tidak memiliki akses edit SP3K.');
    }

    public function edit($id)
    {
        $this->authorizeSp3kEdit();
        $sp3k = WawancaraSp3k::with('wawancara.customer')->findOrFail($id);
        $banks = \App\Models\BankKPR::orderBy('nama')->get();
        $notarisList = \App\Models\Notaris::orderBy('nama_notaris')->get();
        return view('admin.transaksi.acc_bank.edit', compact('sp3k', 'banks', 'notarisList'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeSp3kEdit();
        $sp3k = WawancaraSp3k::findOrFail($id);
        $request->validate([
            'acc_plafon' => 'required', 'tenor' => 'required|integer|min:1',
            'tgl_terbit_sp3k' => 'required|date', 'no_sp3k' => 'required|string|max:255',
            'id_bank_kpr' => 'required|exists:bank_kpr,id', 'id_notaris' => 'required|exists:notaris,id',
            'catatan_acc' => 'nullable|string', 'lampiran' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);
        $dp = app(\App\Services\Sp3kDpService::class)->calculate($request);
        $issued = Carbon::parse($request->tgl_terbit_sp3k)->startOfDay();
        $expires = $issued->copy()->addDays(90);
        $values = [
            ...$dp, 'acc_plafon' => (int) str_replace('.', '', $request->acc_plafon),
            'tenor' => $request->tenor, 'tgl_terbit_sp3k' => $issued, 'tgl_expired' => $expires,
            'no_sp3k' => $request->no_sp3k, 'id_bank_kpr' => $request->id_bank_kpr,
            'id_notaris' => $request->id_notaris, 'catatan_acc' => $request->catatan_acc ?? '',
            'status' => $expires->lt(Carbon::today('Asia/Jakarta')) ? 2 : 1,
        ];
        $filename = null;
        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');
            $filename = \Illuminate\Support\Str::random(25) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('assets/SP3K'), $filename);
            $values['lampiran'] = $filename;
        }
        try {
            DB::transaction(function () use ($sp3k, $values) {
                $sp3k->update($values);
                $this->logEdit('SP3K', $sp3k->id);
            });
        } catch (\Throwable $e) {
            if ($filename && is_file(public_path('assets/SP3K/' . $filename))) unlink(public_path('assets/SP3K/' . $filename));
            throw $e;
        }
        if ($request->expectsJson()) return response()->json(['success' => true]);
        return redirect()->route('acc-bank.edit', $sp3k->id)->with('success', 'SP3K berhasil diperbarui.');
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $data = WawancaraSp3k::with('wawancara', 'wawancara.customer')->findOrFail($id);

            $data->wawancara->update([
                'status' => 1,
            ]);

            $data->wawancara->customer->update([
                'id_status_progres' => 7,
            ]);

            $data->delete();

            $this->logDelete('Wawancara ACC BANK', $data->id);

            DB::commit();

            return response()->json([
                'success' => true,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error batalkan SP3K : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage(),
            ], 500);
        }
    }
}
