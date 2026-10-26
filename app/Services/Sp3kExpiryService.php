<?php

namespace App\Services;

use App\Models\Wawancara;
use App\Models\WawancaraSp3k;
use Illuminate\Support\Facades\DB;

class Sp3kExpiryService
{
    public function refresh(): void
    {
        DB::transaction(function () {
            $expired = WawancaraSp3k::where('status', 1)
                ->whereDate('tgl_expired', '<', now('Asia/Jakarta')->toDateString())
                ->lockForUpdate()->get();

            if ($expired->isEmpty()) return;

            WawancaraSp3k::whereIn('id', $expired->pluck('id'))->update(['status' => 2]);
            Wawancara::whereIn('id', $expired->pluck('id_wawancara')->unique())->update(['status' => 1]);
        });
    }
}
