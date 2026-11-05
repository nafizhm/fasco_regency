<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\CustomerReceivableService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CompleteCustomerReceivables extends Command
{
    protected $signature = 'customer:complete-receivables';
    protected $description = 'Kapitalisasi nama customer dan isi tagihan unit terjual yang belum memiliki piutang';

    public function handle(CustomerReceivableService $service): int
    {
        [$names, $bills] = DB::transaction(function () use ($service) {
            $names = $bills = 0;
            foreach (Customer::orderBy('id')->lockForUpdate()->get() as $customer) {
                $name = $customer->nama_lengkap;
                $customer->nama_lengkap = $name;
                if ($customer->isDirty('nama_lengkap')) {
                    $customer->saveQuietly();
                    $names++;
                }
                if ($service->ensureForCustomer($customer->id)) $bills++;
            }

            return [$names, $bills];
        });

        $this->info("Nama dikapitalisasi: {$names}. Tagihan dibuat: {$bills}.");

        return self::SUCCESS;
    }
}
