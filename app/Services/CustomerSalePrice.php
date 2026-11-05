<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PengajuanHold;
use App\Models\Piutang;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerSalePrice
{
    public function values(Customer $customer, int $gross, int $discount): array
    {
        if ($gross <= 0 || $discount < 0 || $discount > $gross) {
            throw ValidationException::withMessages(['diskon' => 'Diskon tidak boleh negatif atau melebihi harga jual sebelum diskon.']);
        }
        $other = max(0, (int) $customer->total_harga - (int) $customer->hrg_jual);
        $total = $gross - $discount + $other;
        if ($total <= 0 || $total > 2147483647) {
            throw ValidationException::withMessages(['harga_jual_awal' => 'Total harga harus lebih dari Rp0 dan maksimal Rp2.147.483.647.']);
        }

        return ['harga_jual_awal' => $gross, 'diskon' => $discount, 'hrg_jual' => $gross - $discount, 'total_harga' => $total];
    }

    public function sync(Customer $customer, int $gross, int $discount): void
    {
        DB::transaction(function () use ($customer, $gross, $discount) {
            $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            if ($customer->stt_arsip) {
                throw ValidationException::withMessages(['harga_jual_awal' => 'Harga customer arsip tidak dapat diubah.']);
            }
            $values = $this->values($customer, $gross, $discount);
            $delta = $values['total_harga'] - (int) $customer->total_harga;
            $bills = Piutang::where('id_customer', $customer->id)->lockForUpdate()->get();
            if ($delta !== 0 && $bills->isNotEmpty()) {
                $houseBills = $bills->filter(fn ($bill) => str_contains(mb_strtolower($bill->deskripsi ?? ''), 'harga rumah'));
                if ($houseBills->count() !== 1) {
                    throw ValidationException::withMessages(['harga_jual_awal' => 'Tagihan Harga Rumah harus berjumlah satu agar koreksi harga tidak mengubah tagihan lain. Periksa detail tagihan customer.']);
                }
                $bill = $houseBills->first();
                $amount = (int) $bill->nominal + $delta;
                if ($amount < 0) {
                    throw ValidationException::withMessages(['harga_jual_awal' => 'Koreksi melebihi nominal tagihan Harga Rumah. Periksa detail tagihan customer.']);
                }
                DB::table('piutang')->where('id', $bill->id)->update(['nominal' => $amount]);
            }

            $booking = $this->booking($customer);
            if ($booking) {
                $other = $values['total_harga'] - $values['hrg_jual'];
                // Freeze this sale's breakdown; master unit prices remain independent of the agreement.
                $items = $booking->rincian_biaya_penjualan ?? $booking->kavling?->rincian_biaya ?? [];
                $extras = array_values(array_filter($items, fn ($item) => ($item['nama'] ?? '') !== 'Harga Rumah'));
                if ((int) collect($extras)->sum('nilai') !== $other) {
                    $extras = $other > 0 ? [['nama' => 'Biaya Lainnya', 'nilai' => $other]] : [];
                }
                $booking->update(['diskon' => $discount, 'total_harga' => $values['total_harga'],
                    'rincian_biaya_penjualan' => array_merge([['nama' => 'Harga Rumah', 'nilai' => $gross]], $extras)]);
                $values['id_booking'] = $booking->id;
            }
            $customer->update($values);
            DB::table('sppr')->where('id_customer', $customer->id)->update(['harga_jual' => $values['hrg_jual']]);
            if ($bills->isEmpty()) app(CustomerReceivableService::class)->ensureForCustomer($customer->id);
            app(PiutangPaymentService::class)->syncCustomer($customer->id);
        });
    }

    private function booking(Customer $customer): ?PengajuanHold
    {
        if ($customer->id_booking) {
            $booking = PengajuanHold::whereKey($customer->id_booking)->lockForUpdate()->firstOrFail();
            if ((int) $booking->id_kavling !== (int) $customer->id_kavling) {
                throw ValidationException::withMessages(['harga_jual_awal' => 'Unit booking awal tidak sesuai unit customer.']);
            }
            return $booking;
        }
        $bookings = PengajuanHold::where('id_kavling', $customer->id_kavling)->where('stt_reg', 2)
            ->when($customer->nik, fn ($q) => $q->where('nik', $customer->nik))->lockForUpdate()->get();
        if ($bookings->count() > 1) {
            throw ValidationException::withMessages(['harga_jual_awal' => 'Ada lebih dari satu booking awal. Hubungan booking perlu diperiksa agar data penjualan tidak salah diperbarui.']);
        }
        return $bookings->first();
    }
}
