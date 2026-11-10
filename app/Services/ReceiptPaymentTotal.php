<?php

namespace App\Services;

use App\Models\Pemasukan;

class ReceiptPaymentTotal
{
    public function total(Pemasukan $payment): float
    {
        $prefix = trim(explode('/', $payment->no_kwitansi ?? '')[0]);
        if ($prefix === '') {
            return (float) $payment->nominal;
        }

        return (float) Pemasukan::where('id_customer', $payment->id_customer)
            ->where(fn ($query) => $query->whereNull('keterangan')->orWhere('keterangan', 'NOT LIKE', 'Biaya ganti nama%'))
            ->get(['no_kwitansi', 'nominal'])
            ->filter(fn ($item) => trim(explode('/', $item->no_kwitansi ?? '')[0]) === $prefix)
            ->sum('nominal');
    }
}
