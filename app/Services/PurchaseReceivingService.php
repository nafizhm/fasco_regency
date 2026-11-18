<?php

namespace App\Services;

use App\Models\PengajuanPembelian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseReceivingService
{
    public function lines(PengajuanPembelian $purchase, ?int $exceptReceipt = null): array
    {
        $received = DB::table('barang_masuk_detail as d')->join('barang_masuk as m', 'm.id', '=', 'd.id_masuk')
            ->where('m.id_pengajuan_pembelian', $purchase->id)
            ->when($exceptReceipt, fn ($q) => $q->where('m.id', '!=', $exceptReceipt))
            ->groupBy('d.id_barang')->selectRaw('d.id_barang, SUM(d.jumlah) as total')->pluck('total', 'id_barang');

        return $purchase->detail()->with('barang.satuan')->orderBy('id')->get()->map(fn ($line) => [
            'id_barang' => $line->id_barang, 'nama' => $line->barang?->nama ?? '-',
            'satuan' => $line->barang?->satuan?->nama_satuan ?? '',
            'harga' => (int) $line->harga, 'subtotal' => (int) $line->jumlah * (int) $line->harga,
            'jumlah' => (int) $line->jumlah, 'diterima' => (int) ($received[$line->id_barang] ?? 0),
            'sisa' => max((int) $line->jumlah - (int) ($received[$line->id_barang] ?? 0), 0),
        ])->all();
    }

    // Every receipt and purchase mutation locks the same parent to serialize deliveries.
    public function validate(Request $request, ?int $exceptReceipt = null): PengajuanPembelian
    {
        $purchase = PengajuanPembelian::lockForUpdate()->findOrFail($request->id_pengajuan_pembelian);
        $fail = fn ($field, $message) => throw ValidationException::withMessages([$field => $message]);
        if ((int) $request->id_supplier !== (int) $purchase->id_supplier) {
            $fail('id_supplier', 'Supplier harus sesuai pengajuan pembelian.');
        }
        if (\Carbon\Carbon::parse($request->tanggal)->toDateString() < $purchase->tanggal) {
            $fail('tanggal', 'Tanggal masuk tidak boleh sebelum pengajuan.');
        }
        $lines = collect($this->lines($purchase, $exceptReceipt))->keyBy('id_barang');
        $total = 0;
        foreach ($request->id_barang as $key => $id) {
            $quantity = (int) str_replace('.', '', $request->jumlah[$key]);
            if (! $lines->has($id)) {
                $fail('id_barang.'.$key, 'Barang tidak tercantum dalam pengajuan.');
            }
            if ($quantity > $lines[$id]['sisa']) {
                $fail('jumlah.'.$key, 'Jumlah datang melebihi sisa pengajuan ('.$lines[$id]['sisa'].').');
            }
            $total += $quantity;
        }
        if ($total <= 0) {
            $fail('jumlah', 'Minimal satu barang harus diterima. Isi 0 untuk barang yang belum datang.');
        }

        return $purchase;
    }
}
