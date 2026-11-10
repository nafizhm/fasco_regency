<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\BarangMasuk;
use App\Models\BarangMasukDetail;
use App\Models\LogHargaBarang;
use App\Models\MutasiStok;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class HargaBarangService
{
    public function handleCreate(BarangMasuk $barangMasuk): void
    {
    }

    public function handleUpdate(BarangMasuk $barangMasuk, Collection $oldDetails): void
    {
    }

    public function handleDelete(BarangMasuk $barangMasuk, Collection $deletedDetails): void
    {
    }

    private function recalculateFromAnchorOrFallback(
        Barang $barang,
        int $barangMasukId,
        string $proses,
        int $qtyLama,
        ?int $hargaLama,
        int $qtyBaru,
        ?int $hargaBaru
    ): array {
        $anchor = LogHargaBarang::where('id_barang', $barang->id)
            ->orderBy('id')
            ->first();

        if ($anchor) {
            $result = $this->replayFromAnchor($barang->id, $anchor);

            if ($result !== null) {
                return [
                    $result['harga'],
                    $result['stok_awal'],
                    'Harga beli barang dihitung ulang dari log acuan dan mutasi stok setelah transaksi barang masuk diubah.',
                ];
            }
        }

        $fallback = $this->fallbackRecalculate($barang, $qtyLama, $hargaLama, $qtyBaru, $hargaBaru);

        return [
            $fallback['harga'],
            $fallback['stok_awal'],
            'Harga beli barang dihitung ulang dengan perhitungan koreksi transaksi karena belum ada log acuan harga sebelumnya.',
        ];
    }

    private function replayFromAnchor(int $barangId, LogHargaBarang $anchor): ?array
    {
        $stok = (int) $anchor->stok_sebelum;
        $harga = (int) $anchor->harga_sebelum;

        $mutasiList = MutasiStok::where('id_barang', $barangId)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        $startFound = false;

        foreach ($mutasiList as $mutasi) {
            if (! $startFound) {
                if ((int) $mutasi->id_barang_masuk === (int) $anchor->id_barang_masuk) {
                    $startFound = true;
                } else {
                    continue;
                }
            }

            if ((int) $mutasi->jumlah > 0 && $mutasi->id_barang_masuk) {
                $detail = BarangMasukDetail::where('id_masuk', $mutasi->id_barang_masuk)
                    ->where('id_barang', $barangId)
                    ->first();

                $hargaMasuk = $detail ? (int) $detail->harga_beli : $harga;
                $harga = $this->calculateAveragePrice($stok, $harga, (int) $mutasi->jumlah, $hargaMasuk);
            }

            $stok += (int) $mutasi->jumlah;
        }

        if (! $startFound) {
            return null;
        }

        return [
            'stok_awal' => (int) $anchor->stok_sebelum,
            'harga' => max($harga, 0),
        ];
    }

    private function fallbackRecalculate(
        Barang $barang,
        int $qtyLama,
        ?int $hargaLama,
        int $qtyBaru,
        ?int $hargaBaru
    ): array {
        $stokSesudah = (int) $barang->stok;
        $hargaSebelum = (int) $barang->harga_beli;
        $stokSebelum = max($stokSesudah - $qtyBaru + $qtyLama, 0);

        if ($qtyLama <= 0 || $hargaLama === null) {
            return [
                'stok_awal' => $stokSebelum,
                'harga' => $qtyBaru > 0 && $hargaBaru !== null
                    ? $this->calculateAveragePrice(max($stokSesudah - $qtyBaru, 0), $hargaSebelum, $qtyBaru, $hargaBaru)
                    : $hargaSebelum,
            ];
        }

        $stokDasar = $stokSebelum - $qtyLama;
        $nilaiSebelum = ($stokSebelum * $hargaSebelum) - ($qtyLama * $hargaLama);

        if ($stokDasar < 0 || $nilaiSebelum < 0) {
            return [
                'stok_awal' => $stokSebelum,
                'harga' => $hargaSebelum,
            ];
        }

        $hargaDasar = $stokDasar > 0 ? (int) round($nilaiSebelum / $stokDasar) : 0;
        $hargaAkhir = $qtyBaru > 0 && $hargaBaru !== null
            ? $this->calculateAveragePrice($stokDasar, $hargaDasar, $qtyBaru, $hargaBaru)
            : $hargaDasar;

        return [
            'stok_awal' => $stokSebelum,
            'harga' => $hargaAkhir,
        ];
    }

    private function calculateAveragePrice(int $qtyLama, int $hargaLama, int $qtyBaru, int $hargaBaru): int
    {
        $totalQty = $qtyLama + $qtyBaru;

        if ($totalQty <= 0) {
            return 0;
        }

        $totalNilai = ($qtyLama * $hargaLama) + ($qtyBaru * $hargaBaru);

        return (int) round($totalNilai / $totalQty);
    }

    private function resolveUnitPrice(Collection $details): float
    {
        $qty = (int) $details->sum('jumlah');

        if ($qty <= 0) {
            return 0;
        }

        return (float) $details->sum('sub_total') / $qty;
    }

    private function writeLog(array $payload): void
    {
        LogHargaBarang::create([
            'id_barang' => $payload['barang']->id,
            'id_barang_masuk' => $payload['barang_masuk']->id,
            'id_user' => Auth::id(),
            'proses' => $payload['proses'],
            'stok_sebelum' => $payload['stok_sebelum'],
            'harga_sebelum' => $payload['harga_sebelum'],
            'qty_lama' => $payload['qty_lama'],
            'harga_lama' => $payload['harga_lama'],
            'qty_baru' => $payload['qty_baru'],
            'harga_baru_input' => $payload['harga_baru_input'],
            'stok_sesudah' => $payload['stok_sesudah'],
            'harga_sesudah' => $payload['harga_sesudah'],
            'keterangan' => $payload['keterangan'],
        ]);
    }
}
