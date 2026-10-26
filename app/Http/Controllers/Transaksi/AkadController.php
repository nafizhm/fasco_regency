<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\{Akad, AkadDetail, Customer};
use App\Traits\LogAktivitasTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;

class AkadController extends Controller
{
    use LogAktivitasTrait;
    public function index(Request $request)
    {
        $permissions = HakAksesController::getUserPermissions();
        if ($request->ajax()) {
            $data = DB::table('detail_akad as d')->join('akad as a', 'a.id', '=', 'd.id_akad')
                ->leftJoin('customer as c', 'c.id', '=', 'd.id_customer')
                ->leftJoin('kavling_peta as k', 'k.id', '=', 'c.id_kavling')
                ->leftJoin('bank_kpr as b', 'b.id', '=', 'c.id_bank_kpr')
                ->select('d.id', 'a.tgl_akad', 'c.nama_lengkap', 'c.tgl_booking', 'b.nama as bank_kpr', 'k.nama_cluster', 'k.kode_kavling', 'd.jenis_akad', 'd.keterangan', 'd.status')
                ->orderByDesc('d.id');
            return DataTables::of($data)->addIndexColumn()
                ->editColumn('tgl_akad', fn ($row) => $row->tgl_akad ? Carbon::parse($row->tgl_akad)->locale('id')->translatedFormat('d F Y') : 'Belum diisi')
                ->addColumn('status_label', fn ($row) => (int) $row->status === 2 ? 'Selesai akad' : 'Data jadwal lama (belum selesai)')
                ->addColumn('unit', fn ($row) => trim(($row->nama_cluster ?? '').' '.($row->kode_kavling ?? '')))
                ->addColumn('action', function ($row) use ($permissions) {
                    $btn = '';
                    if ($permissions['edit']) {
                        $btn .= '<button class="btn btn-primary btn-xs mx-1 edit-button" data-url="'.e(route('akad.edit', $row->id)).'">Edit</button>';
                    }
                    if ($permissions['hapus']) {
                        $btn .= '<form action="'.e(route('akad.destroy', $row->id)).'" method="POST" style="display:inline">'.csrf_field().method_field('DELETE').'<button class="delete-button btn btn-danger btn-xs">Hapus</button></form>';
                    }
                    return $btn;
                })->rawColumns(['action'])->make(true);
        }
        $customers = Customer::orderBy('nama_lengkap')->get(['id', 'nama_lengkap']);
        $banks = \App\Models\BankKPR::orderBy('nama')->get();
        $marketings = \App\Models\MarketingOffline::orderBy('nama_marketing')->get();
        return view('admin.transaksi.akad.index', compact('permissions', 'customers', 'banks', 'marketings'));
    }

    private function validated(Request $request, $id = null): array
    {
        $unknownDate = $id && AkadDetail::whereKey($id)->whereHas('customer')->exists()
            && Akad::whereKey(AkadDetail::findOrFail($id)->id_akad)->whereNull('tgl_akad')->exists();
        return $request->validate([
            'id_customer' => ['required', 'integer', Rule::exists('customer', 'id')],
            'tgl_akad' => [$unknownDate ? 'nullable' : 'required', 'date_format:Y-m-d', 'before_or_equal:'.now('Asia/Jakarta')->toDateString()],
            'jenis_akad' => ['required', Rule::in(['AJB', 'PPJB'])],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'buyer' => ['sometimes', 'array:nama_lengkap,nik,no_telp,alamat_ktp,tgl_booking,id_bank_kpr,id_marketing'],
            'buyer.nama_lengkap' => ['required_with:buyer', 'string', 'max:100'],
            'buyer.nik' => ['nullable', 'string', 'max:50'],
            'buyer.no_telp' => ['nullable', 'string', 'max:20'],
            'buyer.alamat_ktp' => ['nullable', 'string'],
            'buyer.tgl_booking' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.now('Asia/Jakarta')->toDateString()],
            'buyer.id_bank_kpr' => ['nullable', 'integer', 'exists:bank_kpr,id'],
            'buyer.id_marketing' => ['nullable', 'integer', 'exists:marketing_offline,id'],
        ]);
    }

    public function store(Request $request)
    {
        return $this->saveRecord($this->validated($request));
    }

    public function update(Request $request, $id)
    {
        AkadDetail::findOrFail($id);
        return $this->saveRecord($this->validated($request, $id), $id);
    }

    private function saveRecord(array $input, $id = null)
    {
        DB::transaction(function () use ($input, $id) {
            // Lock the buyer so simultaneous requests cannot create duplicate completed akad.
            $customer = Customer::whereKey($input['id_customer'])->lockForUpdate()->firstOrFail();
            if ($id && isset($input['buyer'])) {
                if (!empty($input['tgl_akad']) && !empty($input['buyer']['tgl_booking']) && $input['buyer']['tgl_booking'] > $input['tgl_akad']) {
                    throw ValidationException::withMessages(['buyer.tgl_booking' => 'Tanggal booking tidak boleh setelah tanggal akad.']);
                }
                $customer->fill($input['buyer'])->save();
            }
            $detail = $id ? AkadDetail::lockForUpdate()->findOrFail($id) : new AkadDetail;
            if (AkadDetail::where('id_customer', $input['id_customer'])->where('status', 2)
                ->when($id, fn ($q) => $q->where('id', '!=', $id))->exists()) {
                throw ValidationException::withMessages(['id_customer' => 'Pembeli ini sudah memiliki data akad selesai.']);
            }
            $oldCustomer = $detail->id_customer;
            $oldAkad = $detail->id_akad;
            // Split legacy group records when edited, preserving the other participants.
            $akad = $oldAkad && AkadDetail::where('id_akad', $oldAkad)->count() === 1
                ? Akad::findOrFail($oldAkad) : new Akad;
            $akad->fill(['tgl_akad' => $input['tgl_akad'] ?? null, 'keterangan' => $input['keterangan'] ?? ''])->save();
            $detail->fill([
                'id_akad' => $akad->id, 'id_customer' => $input['id_customer'],
                'status' => 2, 'jenis_akad' => $input['jenis_akad'],
                'keterangan' => $input['keterangan'] ?? '',
            ]);
            $detail->save();
            Customer::whereKey($input['id_customer'])->update(['id_status_progres' => 3]);
            if ($oldCustomer && $oldCustomer != $input['id_customer']) $this->restoreProgress($oldCustomer);
            $id ? $this->logEdit('Akad', $detail->id) : $this->logCreate('Akad', $detail->id);
        });
        return response()->json(['status' => 'success']);
    }

    public function edit($id)
    {
        $detail = AkadDetail::findOrFail($id);
        $data = $detail->toArray();
        $data['tgl_akad'] = Akad::findOrFail($detail->id_akad)->tgl_akad;
        $data['buyer'] = $detail->customer?->only(['nama_lengkap', 'nik', 'no_telp', 'alamat_ktp', 'tgl_booking', 'id_bank_kpr', 'id_marketing']);
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $detail = AkadDetail::lockForUpdate()->findOrFail($id);
            $akadId = $detail->id_akad;
            $customerId = $detail->id_customer;
            $this->logDelete('Akad', $detail->id);
            $detail->delete();
            if (!AkadDetail::where('id_akad', $akadId)->exists()) Akad::whereKey($akadId)->delete();
            $this->restoreProgress($customerId);
        });
        return response()->json(['status' => 'success']);
    }

    private function restoreProgress($customerId): void
    {
        if (!AkadDetail::where('id_customer', $customerId)->where('status', 2)->exists()) {
            Customer::whereKey($customerId)->where('id_status_progres', 3)->update(['id_status_progres' => 7]);
        }
    }
}
