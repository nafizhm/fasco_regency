<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerKprDp;
use Illuminate\Http\Request;

class CustomerDpController extends Controller
{
    use \App\Traits\InventoryAccess;

    private function authorizeEdit(): void
    {
        $this->authorizeInventory('customer.index', 'edit');
        abort_if((int) auth()->user()->id_role === 2, 403, 'Koreksi DP harus dilakukan admin.');
    }

    public function show(Customer $customer)
    {
        $this->authorizeEdit();
        abort_unless(strtoupper(trim($customer->jenis_pembelian ?? '')) === 'KPR' && ! $customer->stt_arsip, 422);

        return response()->json(['nama' => $customer->nama_lengkap, 'dp_kpr' => $customer->dp_kpr,
            'total_harga' => $customer->total_harga, 'url' => route('customer.dp-kpr.update', $customer)]);
    }

    public function update(Request $request, Customer $customer, CustomerKprDp $service)
    {
        $this->authorizeEdit();
        $raw = (string) $request->input('dp_kpr', '');
        if (! preg_match('/^\d+(?:\.\d{3})*$/', $raw)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['dp_kpr' => 'Isi nominal DP yang valid, misalnya 30.000.000.']);
        }
        $request->merge(['dp_kpr' => str_replace('.', '', $raw)]);
        $request->validate(['dp_kpr' => 'required|integer|min:0|max:2147483647']);
        $service->sync($customer->id, (int) $request->dp_kpr);

        return response()->json(['message' => 'DP KPR dan piutang berhasil diperbarui. Booking fee dan pembayaran lama sudah diperhitungkan.']);
    }
}
