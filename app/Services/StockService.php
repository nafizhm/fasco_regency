<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\HistoryBarang;
use App\Models\MutasiStok;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function addBarangMasuk($barangMasuk)
    {
        $this->lockItems(collect($barangMasuk->detail->pluck('id_barang'))->merge(
            MutasiStok::where('id_barang_masuk', $barangMasuk->id)->pluck('id_barang')
        ));
        foreach ($barangMasuk->detail as $detail) {
            $this->ensureBaseline($detail->id_barang);

            MutasiStok::create([
                'tanggal'         => $barangMasuk->tanggal,
                'id_barang_masuk' => $barangMasuk->id,
                'id_barang'       => $detail->id_barang,
                'jumlah'          => $detail->jumlah,
            ]);
        }
        $this->recalculateForBarangIds(
            $barangMasuk->detail->pluck('id_barang')->unique()
        );
    }

    public function updateBarangMasuk($barangMasuk)
    {
        $this->lockItems(collect($barangMasuk->detail->pluck('id_barang'))->merge(
            MutasiStok::where('id_barang_masuk', $barangMasuk->id)->pluck('id_barang')
        ));
        $oldBarangIds = MutasiStok::where('id_barang_masuk', $barangMasuk->id)
            ->pluck('id_barang')->unique();

        HistoryBarang::where('id_barang_masuk', $barangMasuk->id)->delete();
        MutasiStok::where('id_barang_masuk', $barangMasuk->id)->delete();

        foreach ($barangMasuk->detail as $detail) {
            $this->ensureBaseline($detail->id_barang);

            MutasiStok::create([
                'tanggal'         => $barangMasuk->tanggal,
                'id_barang_masuk' => $barangMasuk->id,
                'id_barang'       => $detail->id_barang,
                'jumlah'          => $detail->jumlah,
            ]);
        }

        $newBarangIds = $barangMasuk->detail->pluck('id_barang')->unique();
        $this->recalculateForBarangIds($oldBarangIds->merge($newBarangIds)->unique());
    }

    public function deleteBarangMasuk($barangMasuk)
    {
        $this->lockItems(collect($barangMasuk->detail->pluck('id_barang'))->merge(
            MutasiStok::where('id_barang_masuk', $barangMasuk->id)->pluck('id_barang')
        ));
        $barangIds = MutasiStok::where('id_barang_masuk', $barangMasuk->id)
            ->pluck('id_barang')->unique();

        HistoryBarang::where('id_barang_masuk', $barangMasuk->id)->delete();
        MutasiStok::where('id_barang_masuk', $barangMasuk->id)->delete();

        $this->recalculateForBarangIds($barangIds);
    }

    public function addBarangKeluar($barangKeluar)
    {
        $this->lockItems(collect($barangKeluar->detail->pluck('id_barang'))->merge(
            MutasiStok::where('id_barang_keluar', $barangKeluar->id)->pluck('id_barang')
        ));
        foreach ($barangKeluar->detail as $detail) {
            $this->ensureBaseline($detail->id_barang);

            MutasiStok::create([
                'tanggal'          => $barangKeluar->tanggal,
                'id_barang_keluar' => $barangKeluar->id,
                'id_barang'        => $detail->id_barang,
                'jumlah'           => -$detail->jumlah,
            ]);
        }

        $this->recalculateForBarangIds(
            $barangKeluar->detail->pluck('id_barang')->unique()
        );
    }

    public function updateBarangKeluar($barangKeluar)
    {
        $this->lockItems(collect($barangKeluar->detail->pluck('id_barang'))->merge(
            MutasiStok::where('id_barang_keluar', $barangKeluar->id)->pluck('id_barang')
        ));
        $oldBarangIds = MutasiStok::where('id_barang_keluar', $barangKeluar->id)
            ->pluck('id_barang')->unique();

        HistoryBarang::where('id_barang_keluar', $barangKeluar->id)->delete();
        MutasiStok::where('id_barang_keluar', $barangKeluar->id)->delete();

        foreach ($barangKeluar->detail as $detail) {
            $this->ensureBaseline($detail->id_barang);

            MutasiStok::create([
                'tanggal'          => $barangKeluar->tanggal,
                'id_barang_keluar' => $barangKeluar->id,
                'id_barang'        => $detail->id_barang,
                'jumlah'           => -$detail->jumlah,
            ]);
        }

        $newBarangIds = $barangKeluar->detail->pluck('id_barang')->unique();
        $this->recalculateForBarangIds($oldBarangIds->merge($newBarangIds)->unique());
    }

    public function deleteBarangKeluar($barangKeluar)
    {
        $this->lockItems(collect($barangKeluar->detail->pluck('id_barang'))->merge(
            MutasiStok::where('id_barang_keluar', $barangKeluar->id)->pluck('id_barang')
        ));
        $barangIds = MutasiStok::where('id_barang_keluar', $barangKeluar->id)
            ->pluck('id_barang')->unique();

        HistoryBarang::where('id_barang_keluar', $barangKeluar->id)->delete();
        MutasiStok::where('id_barang_keluar', $barangKeluar->id)->delete();

        $this->recalculateForBarangIds($barangIds);
    }

    public function addRealisasiBahan($realisasiBahan, $tanggal = null)
    {
        $this->ensureBaseline($realisasiBahan->id_barang);

        MutasiStok::create([
            'tanggal'            => $tanggal ?? now()->toDateString(),
            'id_realisasi_bahan' => $realisasiBahan->id,
            'id_barang'          => $realisasiBahan->id_barang,
            'jumlah'             => -$realisasiBahan->qty,
        ]);

        $this->recalculateStokSisa($realisasiBahan->id_barang);
    }

    public function deleteRealisasiBahan($realisasiBahan)
    {
        MutasiStok::where('id_realisasi_bahan', $realisasiBahan->id)->delete();
        $this->recalculateStokSisa($realisasiBahan->id_barang);
    }

    public function getCurrentStok($barangId)
    {
        return Barang::whereKey($barangId)->value('stok') ?? 0;
    }

    private function ensureBaseline($barangId)
    {
        $barang = Barang::whereKey($barangId)->lockForUpdate()->first();
        if (!$barang) return;

        $baselineQty = (int) ($barang->stok_awal ?? 0);

        if ($baselineQty <= 0) return;

        $hasMutasi = MutasiStok::where('id_barang', $barangId)->lockForUpdate()->first();
        if ($hasMutasi) return;

        MutasiStok::create([
            'id_barang' => $barangId,
            'jumlah'    => $baselineQty,
            'stok_sisa' => $baselineQty,
        ]);
    }

    private function recalculateForBarangIds($barangIds)
    {
        foreach ($barangIds as $id) {
            $this->recalculateStokSisa($id);
        }
    }

    public function recalculateStokSisa($barangId)
    {
        return DB::transaction(function () use ($barangId) {
            $this->lockItems([$barangId]);
            $this->ensureBaseline($barangId);
            return $this->rebuild($barangId);
        });
    }

    private function lockItems($ids): void
    {
        Barang::whereIn('id', collect($ids)->unique()->sort()->values())->orderBy('id')->lockForUpdate()->get();
    }

    private function rebuild($barangId)
    {
        $mutasiList = MutasiStok::where('id_barang', $barangId)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->lockForUpdate()->get();

        $running = 0;
        foreach ($mutasiList as $m) {
            $running += $m->jumlah;
            if ($running < 0) {
                throw ValidationException::withMessages(['jumlah' => 'Transaksi menyebabkan stok negatif pada tanggal ' . $m->tanggal . '. Periksa jumlah dan urutan tanggal transaksi.']);
            }
            $m->stok_sisa = $running;
            $m->save();

            $this->syncHistoryForMutasi($m);
        }

        $barang = Barang::find($barangId);
        if ($barang) {
            $barang->stok = $running;
            $barang->save();
        }
    }

    private function syncHistoryForMutasi(MutasiStok $mutasi): void
    {
        if (!$mutasi->id_barang_masuk && !$mutasi->id_barang_keluar) {
            return;
        }

        $isMasuk = !empty($mutasi->id_barang_masuk);

        HistoryBarang::updateOrCreate(
            ['id_mutasi_stok' => $mutasi->id],
            [
                'tanggal' => $mutasi->tanggal,
                'id_barang' => $mutasi->id_barang,
                'id_barang_masuk' => $mutasi->id_barang_masuk,
                'id_barang_keluar' => $mutasi->id_barang_keluar,
                'tipe' => $isMasuk ? 'MASUK' : 'KELUAR',
                'masuk' => $isMasuk ? max((int) $mutasi->jumlah, 0) : 0,
                'keluar' => $isMasuk ? 0 : abs((int) $mutasi->jumlah),
                'stok' => (int) $mutasi->stok_sisa,
            ]
        );
    }
}
