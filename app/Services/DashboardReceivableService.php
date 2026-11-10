<?php

namespace App\Services;

use App\Models\Customer;

class DashboardReceivableService
{
    public function summary(): array
    {
        $customers = Customer::where('stt_arsip', 0)
            ->withSum('piutangs as total_tagihan', 'nominal')
            ->withSum(['pemasukans as total_bayar' => function ($query) {
                $query->where(fn ($payment) => $payment->whereNull('keterangan')
                    ->orWhere('keterangan', 'NOT LIKE', 'Biaya ganti nama%'));
            }], 'nominal')
            ->get(['id']);

        $total = 0;
        $count = 0;
        foreach ($customers as $customer) {
            $remaining = max((int) $customer->total_tagihan - (int) $customer->total_bayar, 0);
            if ($remaining > 0) {
                $total += $remaining;
                $count++;
            }
        }

        return ['piutang' => $total, 'piutang_customer' => $count];
    }
}
