<?php

namespace App\Http\Controllers;

use App\Models\BankKPR;
use App\Models\RekapKpr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekapKprController extends Controller
{
    use \App\Traits\InventoryAccess;

    private function permissions(): array
    {
        return (array) DB::table('hak_akses')->join('menu', 'menu.id', '=', 'hak_akses.id_menu')
            ->where('id_user', auth()->id())->where('route_name', 'rekap-kpr.index')->select('hak_akses.*')->first();
    }

    private function banks(): array
    {
        return collect(RekapKpr::BANK)->merge(BankKPR::pluck('nama'))->merge(RekapKpr::distinct()->pluck('bank'))
            ->map(fn ($name) => mb_strtoupper(trim($name)))->filter()->unique()->values()->all();
    }

    public function index(Request $request)
    {
        $this->authorizeInventory('rekap-kpr.index');
        $filters = $request->validate(['bank' => 'nullable|string|max:100', 'q' => 'nullable|string|max:255']);
        $bank = isset($filters['bank']) ? mb_strtoupper(trim($filters['bank'])) : '';
        $q = trim($filters['q'] ?? '');
        $items = RekapKpr::query()->when($bank, fn ($query) => $query->where('bank', $bank))
            ->when($q, fn ($query) => $query->where(function ($query) use ($q) {
                foreach (['nama_user', 'blok', 'perumahan', 'keterangan'] as $field) {
                    $query->orWhere($field, 'like', '%'.$q.'%');
                }
            }))->orderBy('id')->get()->groupBy('bank');
        $banks = $this->banks();
        $groups = collect($bank ? [$bank] : RekapKpr::BANK)->merge($items->keys())->unique();

        return view('admin.rekap_kpr.index', compact('items', 'banks', 'groups', 'bank', 'q') + ['permissions' => $this->permissions()]);
    }

    public function create()
    {
        $this->authorizeInventory('rekap-kpr.index', 'tambah');

        return view('admin.rekap_kpr.form', ['data' => new RekapKpr(['bank' => 'BRI', 'perumahan' => 'FR']), 'banks' => $this->banks()]);
    }

    public function edit($id)
    {
        $this->authorizeInventory('rekap-kpr.index', 'edit');

        return view('admin.rekap_kpr.form', ['data' => RekapKpr::findOrFail($id), 'banks' => $this->banks()]);
    }

    public function show($id)
    {
        $this->authorizeInventory('rekap-kpr.index');

        return view('admin.rekap_kpr.show', ['data' => RekapKpr::findOrFail($id), 'permissions' => $this->permissions()]);
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
        $this->authorizeInventory('rekap-kpr.index', $id ? 'edit' : 'tambah');
        $values = $request->validate([
            'bank' => 'required|string|max:100', 'nama_user' => 'required|string|max:255', 'blok' => 'nullable|string|max:100',
            'tanggal_booking' => 'nullable|date_format:Y-m-d', 'tanggal_itj' => 'nullable|date_format:Y-m-d', 'tanggal_masuk' => 'nullable|date_format:Y-m-d',
            'keterangan' => 'nullable|string|max:5000', 'perumahan' => 'required|string|max:150', 'tandai_merah' => 'nullable|boolean',
        ]);
        $values['bank'] = mb_strtoupper(trim($values['bank']));
        $values['tandai_merah'] = $request->boolean('tandai_merah');
        $data = $id ? RekapKpr::findOrFail($id) : new RekapKpr;
        $data->fill($values)->save();

        return redirect()->route('rekap-kpr.index')->with('success', 'Rekap KPR berhasil disimpan.');
    }

    public function destroy($id)
    {
        $this->authorizeInventory('rekap-kpr.index', 'hapus');
        RekapKpr::findOrFail($id)->delete();

        return redirect()->route('rekap-kpr.index')->with('success','Rekap KPR berhasil dihapus.');
    }
}
