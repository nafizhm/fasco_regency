<?php

namespace App\Http\Controllers\Siteplan;

use App\Http\Controllers\Controller;
use App\Models\KavlingPeta;
use App\Models\LokasiKavling;
use Illuminate\Http\Request;
use App\Models\PengaturanMedia;
use App\Models\Piutang;
use App\Models\Pemasukan;

class PublicSiteplanController extends Controller
{
    private function publicStatus(KavlingPeta $unit): string
    {
        if ($unit->customer) {
            return 'Sold';
        }

        return $unit->is_booked ? 'Hold' : 'Ready';
    }

    /**
     * Display the public siteplan.
     */
     public function index()
    {
        $lokasiKavling = LokasiKavling::with(['masterSvg', 'kavlingPeta' => fn ($query) => $query->withBookingState(), 'kavlingPeta.customer'])
            ->orderBy('urutan', 'asc')
            ->get();

            $bg        = PengaturanMedia::where('jenis_data', 'Background booking')->first();

        $holdColor = '#d1d5db';

        $legend = collect([
            (object) ['status_progres' => 'Ready (warna cluster)', 'warna' => '#ffffff'],
            (object) ['status_progres' => 'Hold', 'warna' => $holdColor],
            (object) ['status_progres' => 'Sold', 'warna' => '#4b5563'],
        ]);
        $clusterColors = \App\Services\SiteplanColors::CLUSTERS;
        foreach ($lokasiKavling as $location) {
            foreach ($location->kavlingPeta as $unit) {
                $unit->public_status = $this->publicStatus($unit);
                $unit->public_color = match ($unit->public_status) {
                    'Sold' => '#4b5563',
                    'Hold' => $holdColor,
                    default => \App\Services\SiteplanColors::cluster($unit),
                };
            }
        }

        return view('public_siteplan.index', compact('lokasiKavling', 'legend', 'bg', 'clusterColors'));
    }

    /**
     * Fetch kavling details for the public popup.
     */
     public function show($id)
    {
        $data = KavlingPeta::withBookingState()
            ->with(['lokasi', 'customer', 'listrikAir'])->findOrFail($id);
        $data->public_status = $this->publicStatus($data);

        $tagihanList   = Piutang::where('id_customer', $data->id_customer)->orderBy('id')->get();
        $pemasukanList = Pemasukan::with('kategori')->where('id_customer', $data->id_customer)->get();

        return response()->json([
            'success'         => true,
            'data'            => $data,
            'tagihan'         => $tagihanList,
            'listrik_air' => $data->listrikAir,
            'pemasukan'       => $pemasukanList,
            'total_tagihan'   => $tagihanList->sum('nominal'),
            'total_pemasukan' => $pemasukanList->sum('nominal'),
        ]);
    }
}
