<?php

namespace App\Services;

use App\Models\KategoriTransaksi;
use App\Models\Pemasukan;
use App\Models\PemasukanRetensi;
use Illuminate\Support\Facades\DB;

class KprDisbursementService
{
    public function akadDate($customerId): ?string
    {
        return DB::table('detail_akad')->join('akad', 'akad.id', '=', 'detail_akad.id_akad')
            ->where('detail_akad.id_customer', $customerId)->where('detail_akad.status', 2)
            ->whereDate('akad.tgl_akad', '<=', now('Asia/Jakarta')->toDateString())
            ->orderBy('akad.tgl_akad')->value('akad.tgl_akad');
    }

    public function summary($customerId): array
    {
        $categories = KategoriTransaksi::where('kategori', 'Pencairan KPR')->pluck('id');
        $query = Pemasukan::where('id_customer', $customerId)->whereIn('id_kategori_transaksi', $categories);
        $total = (int) (clone $query)->sum('nominal');
        $last = (clone $query)->orderByDesc('id')->first();
        $retensi = $last ? PemasukanRetensi::where('id_pemasukan', $last->id)->pluck('nominal', 'id_retensi')->all() : [];
        $plafon = (int) (app(Sp3kPlafonService::class)->latestForCustomer($customerId)?->acc_plafon ?? 0);
        return ['total_pencairan' => $total, 'sisa_plafon' => max($plafon - $total, 0), 'retensi_tersisa' => $retensi];
    }
}
