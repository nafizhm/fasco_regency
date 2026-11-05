<?php

namespace App\Http\Controllers\Pengaturan;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\ReceiptNotes;
use App\Traits\LogAktivitasTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerReceiptNoteController extends Controller
{
    use LogAktivitasTrait;

    private function allowed(string $action = 'lihat'): bool
    {
        return DB::table('hak_akses')->join('menu', 'menu.id', '=', 'hak_akses.id_menu')
            ->where('hak_akses.id_user', auth()->id())->where('menu.route_name', 'customer-receipt-notes.index')
            ->where('hak_akses.lihat', 1)->where('hak_akses.'.$action, 1)->exists();
    }

    public function index(Request $request)
    {
        abort_unless($this->allowed(), 403);
        $request->validate(['customer' => 'nullable|integer|exists:customer,id']);
        $customers = Customer::orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'kode_customer']);
        $customer = $request->filled('customer') ? Customer::findOrFail($request->integer('customer')) : null;
        $defaultText = ReceiptNotes::DEFAULT_TEXT;
        $canEdit = $this->allowed('edit');

        return view('admin.pengaturan.customer_receipt_notes.index', compact('customers', 'customer', 'defaultText', 'canEdit'));
    }

    public function update(Request $request, int $customer)
    {
        abort_unless($this->allowed('edit'), 403);
        $data = $request->validate(['use_default' => 'required|boolean', 'notes' => 'nullable|string|max:10000']);
        $record = Customer::findOrFail($customer);
        DB::transaction(function () use ($record, $data) {
            DB::table('customer')->where('id', $record->id)->update([
                'catatan_kwitansi' => $data['use_default'] ? null : trim($data['notes'] ?? ''),
            ]);
            $this->logEdit('Catatan Kwitansi Customer', $record->id);
        });

        return redirect()->route('customer-receipt-notes.index', ['customer' => $record->id])
            ->with('success', 'Catatan kwitansi '.$record->nama_lengkap.' berhasil disimpan.');
    }
}
