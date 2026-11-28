<?php

namespace App\Http\Controllers;

use App\Models\PengajuanTagihan;
use App\Models\PengajuanTagihanDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PengajuanTagihanController extends Controller
{
    use \App\Traits\InventoryAccess;

    private function permissions(): array
    {
        return (array) DB::table('hak_akses')->join('menu', 'menu.id', '=', 'hak_akses.id_menu')->where('id_user', auth()->id())->where('route_name', 'pengajuan-tagihan.index')->select('hak_akses.*')->first();
    }

    public function index()
    {
        $this->authorizeInventory('pengajuan-tagihan.index');

        return view('admin.pengajuan_tagihan.index', ['items' => PengajuanTagihan::with('detail')->orderByDesc('tanggal')->orderByDesc('id')->get(), 'permissions' => $this->permissions()]);
    }

    public function pdf(Request $request)
    {
        $this->authorizeInventory('pengajuan-tagihan.index');
        $values = $request->validate([
            'ids' => 'nullable|array|max:10000',
            'ids.*' => 'required|integer|distinct',
            'status' => ['nullable', Rule::in(PengajuanTagihan::STATUS)],
            'search' => 'nullable|string|max:1000',
        ]);
        // The applied DataTables rows include the exact smart search and sort order across all pages.
        $ids = $values['ids'] ?? [];
        $lines = PengajuanTagihanDetail::query()->whereIn('id', $ids)
            ->whereHas('pengajuan', fn ($query) => $query->when($values['status'] ?? null,
                fn ($query, $status) => $query->where('status', $status)))
            ->with('pengajuan')->get()->keyBy('id');
        $lines = collect($ids)->map(fn ($id) => $lines->get($id))->filter()->values();

        return Pdf::loadView('admin.pengajuan_tagihan.pdf', [
            'lines' => $lines, 'status' => $values['status'] ?? null, 'search' => $values['search'] ?? null,
        ])->setPaper('a4', 'landscape')->stream('pengajuan-tagihan-'.now('Asia/Jakarta')->format('Ymd-His').'.pdf');
    }

    public function create()
    {
        $this->authorizeInventory('pengajuan-tagihan.index', 'tambah');

        return view(request()->ajax() ? 'admin.pengajuan_tagihan.form_content' : 'admin.pengajuan_tagihan.form', ['data' => new PengajuanTagihan(['nomor' => 'PT-'.now('Asia/Jakarta')->format('Ymd').'-'.strtoupper(\Illuminate\Support\Str::random(6)), 'tanggal' => now('Asia/Jakarta')->toDateString(), 'status' => 'Draft'])]);
    }

    public function edit($id)
    {
        $this->authorizeInventory('pengajuan-tagihan.index', 'edit');

        $data = PengajuanTagihan::with('detail')->findOrFail($id);
        $line = request()->boolean('baru') ? null : (request()->filled('detail')
            ? $data->detail()->findOrFail(request('detail')) : $data->detail->first());

        return view(request()->ajax() ? 'admin.pengajuan_tagihan.form_content' : 'admin.pengajuan_tagihan.form', compact('data', 'line'));
    }

    public function show($id)
    {
        $this->authorizeInventory('pengajuan-tagihan.index');

        return view('admin.pengajuan_tagihan.show', ['data' => PengajuanTagihan::with('detail')->findOrFail($id), 'permissions' => $this->permissions()]);
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
        $this->authorizeInventory('pengajuan-tagihan.index', $id ? 'edit' : 'tambah');
        $rules = [
            'nomor' => ['required', 'string', 'max:100', Rule::unique('pengajuan_tagihan')->ignore($id)],
            'tanggal' => 'required|date_format:Y-m-d', 'status' => ['required', Rule::in(PengajuanTagihan::STATUS)],
            'keterangan' => 'nullable|string|max:5000', 'detail' => 'required|array|size:1',
            'detail.*.id' => 'nullable|integer|distinct', 'detail.*.tanggal_nota_max' => 'nullable|date_format:Y-m-d',
            'detail.*.nama' => 'required|string|max:255', 'detail.*.uraian' => 'required|string|max:5000',
            'detail.*.rekening' => 'nullable|string|max:500', 'detail.*.hapus_lampiran' => 'nullable|boolean',
            'detail.*.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,xls,xlsx,doc,docx|max:10240',
        ];
        foreach (PengajuanTagihan::LOKASI as $key => $label) {
            $rules['detail.*.'.$key] = ['required', 'regex:/^[0-9]+$/', 'numeric', 'min:0', 'max:999999999999'];
        }
        $values = $request->validate($rules);
        $newFiles = [];
        $oldFiles = [];
        try {
            $data = DB::transaction(function () use ($id, $values, &$newFiles, &$oldFiles) {
                $data = $id ? PengajuanTagihan::lockForUpdate()->findOrFail($id) : new PengajuanTagihan;
                $existing = $data->exists ? $data->detail()->get()->keyBy('id') : collect();
                $data->fill(collect($values)->except('detail')->all())->save();
                foreach ($values['detail'] as $line) {
                    $lineId = $line['id'] ?? null;
                    abort_if($lineId && ! $existing->has($lineId), 422, 'Detail tidak sesuai pengajuan.');
                    $item = $lineId ? $existing[$lineId] : $data->detail()->make();
                    $file = $line['file'] ?? null;
                    if ($file || ! empty($line['hapus_lampiran'])) {
                        if ($item->lampiran) {
                            $oldFiles[] = $item->lampiran;
                        }
                        $item->lampiran = $file ? $file->store('pengajuan-tagihan', 'local') : null;
                        if ($file && ! $item->lampiran) {
                            throw new \RuntimeException('Lampiran gagal disimpan.');
                        }
                        $item->nama_lampiran = $file ? $file->getClientOriginalName() : null;
                        if ($item->lampiran) {
                            $newFiles[] = $item->lampiran;
                        }
                    }
                    $item->fill(collect($line)->except(['id', 'file', 'hapus_lampiran'])->all())->save();
                }

                return $data;
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($newFiles);
            throw $e;
        }
        Storage::disk('local')->delete($oldFiles);

        if ($request->expectsJson()) {
            $request->session()->flash('success', 'Pengajuan tagihan berhasil disimpan.');

            return response()->json(['redirect' => route('pengajuan-tagihan.index')]);
        }

        return redirect()->route('pengajuan-tagihan.index')->with('success', 'Pengajuan tagihan berhasil disimpan.');
    }

    public function attachment($id, $line)
    {
        $this->authorizeInventory('pengajuan-tagihan.index');
        $item = PengajuanTagihan::findOrFail($id)->detail()->findOrFail($line);
        abort_unless($item->lampiran && Storage::disk('local')->exists($item->lampiran), 404);

        $disk = Storage::disk('local');
        if (request()->boolean('view') && in_array($disk->mimeType($item->lampiran), ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true)) {
            return $disk->response($item->lampiran, $item->nama_lampiran, ['X-Content-Type-Options' => 'nosniff'], 'inline');
        }

        return $disk->download($item->lampiran, $item->nama_lampiran);
    }

    public function destroy($id)
    {
        $this->authorizeInventory('pengajuan-tagihan.index', 'hapus');
        $files = DB::transaction(function () use ($id) {
            $data = PengajuanTagihan::lockForUpdate()->findOrFail($id);
            if (request()->filled('detail')) {
                $line = $data->detail()->findOrFail(request('detail'));
                $files = array_filter([$line->lampiran]);
                $line->delete();
                if (! $data->detail()->exists()) {
                    $data->delete();
                }
            } else {
                $files = $data->detail()->pluck('lampiran')->filter()->all();
                $data->detail()->delete();
                $data->delete();
            }

            return $files;
        });
        Storage::disk('local')->delete($files);

        return redirect()->route('pengajuan-tagihan.index')->with('success', 'Pengajuan tagihan dihapus.');
    }
}
