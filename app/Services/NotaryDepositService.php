<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NotaryDepositService
{
    public function balance(int $account): int
    {
        return (int) DB::table('notary_deposit_entries')->where('account_id', $account)->sum('amount');
    }

    public function paid(int $bill): int
    {
        return -(int) DB::table('notary_deposit_entries')->where('bill_id', $bill)->sum('amount');
    }

    public function entries(int $account)
    {
        $balance = 0;
        return DB::table('notary_deposit_entries as e')
            ->leftJoin('notary_deposit_bills as b', 'b.id', '=', 'e.bill_id')
            ->leftJoin('kavling_peta as k', 'k.id', '=', 'b.unit_id')
            ->where('e.account_id', $account)->select('e.*', 'k.kode_kavling', 'k.nama_cluster', 'b.items as bill_items')
            ->orderBy('e.entry_date')->orderBy('e.id')->get()->map(function ($entry) use (&$balance) {
                $balance += (int) $entry->amount; $entry->balance = $balance;
                $entry->cost_name = $entry->item_index !== null ? (json_decode($entry->bill_items ?? '[]', true)[$entry->item_index]['name'] ?? '') : '';
                return $entry;
            });
    }

    private function lock(int $account): object
    {
        return DB::table('notary_deposit_accounts')->where('id', $account)->lockForUpdate()->firstOrFail();
    }

    public function bill(int $account, array $data, int $user): int
    {
        return DB::transaction(function () use ($account, $data, $user) {
            $record = $this->lock($account);
            $unit = DB::table('kavling_peta')->where('id', $data['unit_id'])->first();
            if (! $unit || (int) $unit->id_lokasi !== (int) $record->location_id) {
                throw ValidationException::withMessages(['unit_id' => 'Unit harus berasal dari lokasi perumahan rekening deposit ini.']);
            }
            $this->unusedToken('notary_deposit_bills', $data['request_token']);
            $items = array_values(array_map(fn ($item) => ['name' => trim($item['name']), 'amount' => (int) $item['amount']], $data['items']));
            return DB::table('notary_deposit_bills')->insertGetId([
                'account_id' => $account, 'unit_id' => $unit->id, 'bill_date' => $data['bill_date'],
                'reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null,
                'items' => json_encode($items), 'amount' => array_sum(array_column($items, 'amount')),
                'user_id' => $user, 'request_token' => $data['request_token'], 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    public function deposit(int $account, array $data, int $user): int
    {
        if ((int) $data['amount'] <= 0) throw ValidationException::withMessages(['amount' => 'Nominal deposit harus lebih dari nol.']);
        return DB::transaction(function () use ($account, $data, $user) {
            $this->lock($account);
            return $this->insert($account, $data + ['type' => 'deposit'], (int) $data['amount'], null, $user);
        });
    }

    public function pay(int $account, array $data, int $user): int
    {
        if ((int) $data['amount'] <= 0) throw ValidationException::withMessages(['amount' => 'Nominal pemakaian harus lebih dari nol.']);
        return DB::transaction(function () use ($account, $data, $user) {
            $this->lock($account);
            $bill = DB::table('notary_deposit_bills')->where('account_id', $account)->where('id', $data['bill_id'])->whereNull('cancelled_at')->lockForUpdate()->first();
            if (! $bill) throw ValidationException::withMessages(['bill_id' => 'Tagihan tidak ditemukan atau sudah dibatalkan.']);
            if ($data['entry_date'] < $bill->bill_date) throw ValidationException::withMessages(['entry_date' => 'Tanggal pemakaian tidak boleh sebelum tanggal tagihan.']);
            $remaining = (int) $bill->amount - $this->paid($bill->id);
            if ((int) $data['amount'] > $remaining) throw ValidationException::withMessages(['amount' => 'Pemakaian melebihi sisa tagihan unit.']);
            $items = json_decode($bill->items, true);
            $index = $data['item_index'];
            if (! isset($items[$index])) throw ValidationException::withMessages(['item_index' => 'Rincian biaya tidak ditemukan pada tagihan ini.']);
            $paid = -(int) DB::table('notary_deposit_entries')->where('bill_id', $bill->id)->where('item_index', $index)->sum('amount');
            if ((int) $data['amount'] > (int) $items[$index]['amount'] - $paid) {
                throw ValidationException::withMessages(['amount' => 'Pemakaian melebihi sisa jenis biaya yang dipilih.']);
            }
            return $this->insert($account, $data + ['type' => 'payment'], -(int) $data['amount'], $bill->id, $user);
        });
    }

    public function reverse(int $entry, array $data, int $user): int
    {
        $original = DB::table('notary_deposit_entries')->find($entry);
        abort_unless($original, 404);
        return DB::transaction(function () use ($original, $data, $user) {
            $this->lock($original->account_id);
            if ($original->type === 'reversal' || DB::table('notary_deposit_entries')->where('reversal_of', $original->id)->exists()) {
                throw ValidationException::withMessages(['entry' => 'Transaksi pembalik atau transaksi yang sudah dikoreksi tidak dapat dibalik lagi.']);
            }
            return $this->insert($original->account_id, $data + ['type' => 'reversal', 'reversal_of' => $original->id,
                'item_index' => $original->item_index,
                'entry_date' => now('Asia/Jakarta')->toDateString()], -(int) $original->amount, $original->bill_id, $user);
        });
    }

    public function cancelBill(int $account, int $bill): void
    {
        DB::transaction(function () use ($account, $bill) {
            $this->lock($account);
            $record = DB::table('notary_deposit_bills')->where('account_id', $account)->where('id', $bill)->whereNull('cancelled_at')->first();
            abort_unless($record, 404);
            if ($this->paid($bill) !== 0) throw ValidationException::withMessages(['bill' => 'Balikkan pemakaian deposit untuk tagihan ini sebelum membatalkannya.']);
            DB::table('notary_deposit_bills')->where('id', $bill)->update(['cancelled_at' => now(), 'updated_at' => now()]);
        });
    }

    private function unusedToken(string $table, string $token): void
    {
        if (DB::table($table)->where('request_token', $token)->exists()) {
            throw ValidationException::withMessages(['request_token' => 'Form ini sudah disimpan. Muat ulang halaman sebelum mencatat transaksi baru.']);
        }
    }

    private function insert(int $account, array $data, int $amount, ?int $bill, int $user): int
    {
        if ($amount === 0) throw ValidationException::withMessages(['amount' => 'Nominal harus lebih dari nol.']);
        $this->unusedToken('notary_deposit_entries', $data['request_token']);
        $id = DB::table('notary_deposit_entries')->insertGetId([
            'account_id' => $account, 'bill_id' => $bill, 'entry_date' => $data['entry_date'], 'type' => $data['type'],
            'item_index' => $data['item_index'] ?? null,
            'amount' => $amount, 'reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null,
            'attachment' => $data['attachment'] ?? null, 'attachment_name' => $data['attachment_name'] ?? null,
            'reversal_of' => $data['reversal_of'] ?? null, 'request_token' => $data['request_token'],
            'user_id' => $user, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $balance = 0;
        foreach (DB::table('notary_deposit_entries')->where('account_id', $account)->orderBy('entry_date')->orderBy('id')->get() as $entry) {
            $balance += (int) $entry->amount;
            if ($balance < 0) throw ValidationException::withMessages(['amount' => 'Saldo deposit tidak mencukupi pada tanggal transaksi. Periksa nominal dan tanggal deposit/pemakaian.']);
        }
        return $id;
    }
}
