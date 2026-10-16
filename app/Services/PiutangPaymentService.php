<?php

namespace App\Services;

use App\Models\Pemasukan;
use App\Models\Piutang;
use Illuminate\Support\Facades\DB;

class PiutangPaymentService
{
    public function syncAll(): void
    {
        foreach (Piutang::where('id_customer', '>', 0)->distinct()->pluck('id_customer') as $id) {
            $this->syncCustomer($id);
        }
        foreach (Piutang::where(function ($query) {
            $query->whereNull('id_customer')->orWhere('id_customer', 0);
        })->pluck('id') as $id) {
            $this->syncPiutang($id);
        }
    }

    public function syncPiutang($id): array
    {
        $piutang = Piutang::find($id);
        if (!$piutang) return [];
        if ($piutang->id_customer) return $this->syncCustomer($piutang->id_customer)[$id] ?? [];

        return DB::transaction(function () use ($id) {
            $bills = Piutang::whereKey($id)->lockForUpdate()->get();
            $payments = Pemasukan::where('id_piutang', $id)->orderBy('tanggal')->orderBy('id')->get();
            return $this->allocate($bills, $payments)[$id] ?? [];
        });
    }

    public function syncCustomer($id): array
    {
        if (!$id) return [];
        return DB::transaction(function () use ($id) {
            $bills = Piutang::where('id_customer', $id)->orderBy('id')->lockForUpdate()->get();
            if ($bills->isEmpty()) return [];
            $payments = Pemasukan::where(function ($query) use ($id, $bills) {
                $query->where('id_customer', $id)->orWhereIn('id_piutang', $bills->pluck('id'));
            })->where(function ($query) {
                $query->whereNull('keterangan')->orWhere('keterangan', 'NOT LIKE', 'Biaya ganti nama%');
            })->orderBy('tanggal')->orderBy('id')->get();
            return $this->allocate($bills, $payments);
        });
    }

    private function allocate($bills, $payments): array
    {
        $ledger = [];
        $remaining = [];
        foreach ($bills as $bill) {
            $ledger[$bill->id] = [];
            $remaining[$bill->id] = max((int) $bill->nominal, 0);
        }
        // Reserve explicitly linked payments before distributing general customer payments.
        foreach ([true, false] as $linked) {
            foreach ($payments as $payment) {
                if ((bool) $payment->id_piutang !== $linked) continue;
                $amount = max((int) $payment->nominal, 0);
                $targets = $linked ? [$payment->id_piutang] : $bills->pluck('id')->all();
                foreach ($targets as $id) {
                    $allocated = min($amount, $remaining[$id] ?? 0);
                    if ($allocated <= 0) continue;
                    $remaining[$id] -= $allocated;
                    $amount -= $allocated;
                    $ledger[$id][] = [
                        'id' => $payment->id,
                        'tanggal' => $payment->tanggal,
                        'no_kwitansi' => $payment->no_kwitansi,
                        'keterangan' => $payment->keterangan,
                        'nominal_transaksi' => (int) $payment->nominal,
                        'nominal' => $allocated,
                        'lampiran' => $payment->lampiran,
                    ];
                    if ($amount <= 0) break;
                }
            }
        }
        foreach ($bills as $bill) {
            $paid = array_sum(array_column($ledger[$bill->id], 'nominal'));
            $settled = $remaining[$bill->id] === 0;
            $dates = array_filter(array_column($ledger[$bill->id], 'tanggal'));
            $values = [
                'terbayar' => $paid,
                'sisa_bayar' => $remaining[$bill->id],
                'status' => $settled ? 2 : 1,
                'tgl_pelunasan' => $settled && $dates ? max($dates) : null,
            ];
            $changed = false;
            foreach ($values as $key => $value) {
                if ((string) $bill->$key !== (string) $value) $changed = true;
            }
            if ($changed) DB::table('piutang')->where('id', $bill->id)->update($values);
            usort($ledger[$bill->id], fn ($a, $b) => [$a['tanggal'], $a['id']] <=> [$b['tanggal'], $b['id']]);
        }
        return $ledger;
    }

    public function paymentChanged(Pemasukan $payment): void
    {
        $customers = [$payment->id_customer, $payment->getRawOriginal('id_customer')];
        $billIds = [$payment->id_piutang, $payment->getRawOriginal('id_piutang')];
        foreach (array_unique(array_filter($customers)) as $id) $this->syncCustomer($id);
        foreach (array_unique(array_filter($billIds)) as $id) $this->syncPiutang($id);
    }
}
