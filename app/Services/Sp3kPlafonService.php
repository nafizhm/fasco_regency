<?php

namespace App\Services;

use App\Models\WawancaraSp3k;

class Sp3kPlafonService
{
    public function latestForCustomer($customerId, bool $lock = false): ?WawancaraSp3k
    {
        $query = WawancaraSp3k::whereHas('wawancara', fn ($q) => $q->where('id_customer', $customerId))
            ->orderByDesc('tgl_terbit_sp3k')->orderByDesc('id');
        if ($lock) $query->lockForUpdate();
        return $query->first();
    }
}
