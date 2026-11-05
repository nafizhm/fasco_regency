<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\{Notaris, LokasiKavling};
use App\Services\NotaryDepositService;
use App\Traits\LogAktivitasTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\{Rule, ValidationException};

class NotaryDepositController extends Controller
{
    use LogAktivitasTrait;

    public function __construct(private NotaryDepositService $ledger) {}

    private function allowed(string $action = 'lihat'): bool
    {
        return DB::table('hak_akses')->join('menu', 'menu.id', '=', 'hak_akses.id_menu')
            ->where('hak_akses.id_user', auth()->id())->where('menu.route_name', 'notary-deposits.index')
            ->where('hak_akses.lihat', 1)->where('hak_akses.'.$action, 1)->exists();
    }

    private function authorizeAction(string $action = 'lihat'): void
    {
        abort_unless($this->allowed($action), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeAction();
        $request->validate(['notary' => 'nullable|integer', 'location' => 'nullable|integer']);
        $balances = DB::table('notary_deposit_entries')->select('account_id')->selectRaw('SUM(amount) as balance')->groupBy('account_id');
        $accounts = DB::table('notary_deposit_accounts as a')->join('notaris as n', 'n.id', '=', 'a.notary_id')
            ->join('lokasi_kavling as l', 'l.id', '=', 'a.location_id')->leftJoinSub($balances, 's', 's.account_id', '=', 'a.id')
            ->when($request->filled('notary'), fn ($q) => $q->where('a.notary_id', $request->integer('notary')))
            ->when($request->filled('location'), fn ($q) => $q->where('a.location_id', $request->integer('location')))
            ->select('a.*', 'n.nama_notaris', 'l.nama_kavling')->selectRaw('COALESCE(s.balance, 0) as balance')
            ->orderBy('n.nama_notaris')->orderBy('l.nama_kavling')->get();
        $notaries = Notaris::orderBy('nama_notaris')->get();
        $locations = LokasiKavling::orderBy('nama_kavling')->get();
        $canAdd = $this->allowed('tambah');
        return view('admin.keuangan.notary_deposits.index', compact('accounts', 'notaries', 'locations', 'canAdd'));
    }

    public function store(Request $request)
    {
        $this->authorizeAction('tambah');
        $data = $request->validate([
            'notary_id' => ['required', 'integer', 'exists:notaris,id', Rule::unique('notary_deposit_accounts')->where('location_id', $request->location_id)],
            'location_id' => 'required|integer|exists:lokasi_kavling,id',
        ], ['notary_id.unique' => 'Rekening deposit untuk notaris dan lokasi ini sudah tersedia.']);
        $id = DB::transaction(function () use ($data) {
            DB::table('notaris')->where('id', $data['notary_id'])->lockForUpdate()->firstOrFail();
            if (DB::table('notary_deposit_accounts')->where('notary_id', $data['notary_id'])->where('location_id', $data['location_id'])->exists()) {
                throw ValidationException::withMessages(['notary_id' => 'Rekening deposit untuk notaris dan lokasi ini sudah tersedia.']);
            }
            $id = DB::table('notary_deposit_accounts')->insertGetId($data + ['created_at' => now(), 'updated_at' => now()]);
            $this->logCreate('Rekening Deposit Notaris', $id);
            return $id;
        });
        return redirect()->route('notary-deposits.show', $id)->with('success', 'Rekening deposit dibuat. Catat saldo awal atau setoran melalui Tambah Deposit.');
    }

    private function account(int $account): object
    {
        return DB::table('notary_deposit_accounts as a')->join('notaris as n', 'n.id', '=', 'a.notary_id')
            ->join('lokasi_kavling as l', 'l.id', '=', 'a.location_id')->where('a.id', $account)
            ->select('a.*', 'n.nama_notaris', 'l.nama_kavling')->firstOrFail();
    }

    private function detail(int $account): array
    {
        $record = $this->account($account);
        $entries = $this->ledger->entries($account);
        $paid = DB::table('notary_deposit_entries')->where('account_id', $account)->whereNotNull('bill_id')
            ->select('bill_id')->selectRaw('-SUM(amount) as paid')->groupBy('bill_id');
        $bills = DB::table('notary_deposit_bills as b')->join('kavling_peta as k', 'k.id', '=', 'b.unit_id')
            ->leftJoinSub($paid, 'p', 'p.bill_id', '=', 'b.id')->where('b.account_id', $account)
            ->select('b.*', 'k.kode_kavling', 'k.nama_cluster')->selectRaw('COALESCE(p.paid, 0) as paid')
            ->orderByDesc('b.bill_date')->orderByDesc('b.id')->get();
        foreach ($bills as $bill) {
            $bill->item_balances = collect(json_decode($bill->items, true))->map(function ($item, $index) use ($entries, $bill) {
                $paid = -(int) $entries->where('bill_id', $bill->id)->where('item_index', $index)->sum('amount');
                return $item + ['index' => $index, 'paid' => $paid, 'remaining' => max(0, (int) $item['amount'] - $paid)];
            })->all();
        }
        $balance = $this->ledger->balance($account);
        $unpaid = $bills->whereNull('cancelled_at')->filter(fn ($b) => (int) $b->amount > (int) $b->paid);
        $outstanding = $unpaid->sum(fn ($b) => (int) $b->amount - (int) $b->paid);
        return compact('record', 'entries', 'bills', 'balance', 'unpaid', 'outstanding');
    }

    public function show(int $account)
    {
        $this->authorizeAction();
        $data = $this->detail($account);
        $data['units'] = DB::table('kavling_peta')->where('id_lokasi', $data['record']->location_id)->orderBy('nama_cluster')->orderBy('kode_kavling')->get(['id', 'kode_kavling', 'nama_cluster']);
        $data['canAdd'] = $this->allowed('tambah'); $data['canEdit'] = $this->allowed('edit'); $data['canDelete'] = $this->allowed('hapus');
        return view('admin.keuangan.notary_deposits.show', $data);
    }

    private function money(Request $request, string $field): void
    {
        $value = $request->input($field, '');
        if (! is_string($value) && ! is_int($value)) throw ValidationException::withMessages([$field => 'Isi nominal rupiah yang valid.']);
        $raw = (string) $value;
        if (! preg_match('/^\d+(?:\.\d{3})*$/', $raw)) throw ValidationException::withMessages([$field => 'Isi nominal rupiah yang valid, tanpa desimal atau tanda minus.']);
        $input = $request->input();
        data_set($input, $field, str_replace('.', '', $raw));
        $request->replace($input);
    }

    private function transactionData(Request $request): array
    {
        $this->money($request, 'amount');
        return $request->validate([
            'amount' => 'required|integer|min:1|max:1000000000000',
            'entry_date' => 'required|date_format:Y-m-d|before_or_equal:'.now('Asia/Jakarta')->toDateString(),
            'request_token' => 'required|uuid', 'reference' => 'nullable|string|max:255', 'notes' => 'nullable|string|max:5000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);
    }

    public function deposit(Request $request, int $account)
    {
        $this->authorizeAction('tambah');
        return $this->saveEntry($request, $account, $this->transactionData($request), false);
    }

    public function pay(Request $request, int $account)
    {
        $this->authorizeAction('tambah');
        $data = $this->transactionData($request);
        $data += $request->validate(['bill_id' => 'required|integer', 'item_index' => 'required|integer|min:0|max:29']);
        return $this->saveEntry($request, $account, $data, true);
    }

    private function saveEntry(Request $request, int $account, array $data, bool $payment)
    {
        $this->account($account);
        $path = null;
        unset($data['attachment']);
        try {
            if ($request->hasFile('attachment')) {
                $path = $request->file('attachment')->store('notary-deposits', 'local');
                if (! $path) throw new \RuntimeException('Lampiran gagal disimpan.');
                $data['attachment'] = $path; $data['attachment_name'] = basename($request->file('attachment')->getClientOriginalName());
            }
            DB::transaction(function () use ($account, $data, $payment) {
                $id = $payment ? $this->ledger->pay($account, $data, auth()->id()) : $this->ledger->deposit($account, $data, auth()->id());
                $this->logCreate($payment ? 'Pemakaian Deposit Notaris' : 'Setoran Deposit Notaris', $id);
            });
        } catch (\Throwable $e) {
            if ($path) Storage::disk('local')->delete($path);
            throw $e;
        }
        return redirect()->route('notary-deposits.show', $account)->with('success', $payment ? 'Pemakaian deposit tercatat dan sisa tagihan diperbarui.' : 'Deposit berhasil ditambahkan.');
    }

    public function bill(Request $request, int $account)
    {
        $this->authorizeAction('tambah');
        $request->validate(['items' => 'required|array|min:1|max:30', 'items.*' => 'required|array']);
        foreach ($request->input('items', []) as $index => $item) $this->money($request, 'items.'.$index.'.amount');
        $data = $request->validate([
            'unit_id' => 'required|integer', 'bill_date' => 'required|date_format:Y-m-d|before_or_equal:'.now('Asia/Jakarta')->toDateString(),
            'reference' => 'nullable|string|max:255', 'notes' => 'nullable|string|max:5000', 'request_token' => 'required|uuid',
            'items' => 'required|array|min:1|max:30', 'items.*.name' => 'required|string|max:255', 'items.*.amount' => 'required|integer|min:1|max:1000000000000',
        ]);
        DB::transaction(function () use ($account, $data) {
            $id = $this->ledger->bill($account, $data, auth()->id()); $this->logCreate('Tagihan Notaris per Unit', $id);
        });
        return redirect()->route('notary-deposits.show', $account)->with('success', 'Tagihan unit dicatat. Saldo deposit berkurang ketika pemakaian deposit disimpan.');
    }

    public function reverse(Request $request, int $entry)
    {
        $this->authorizeAction('edit');
        $data = $request->validate(['notes' => 'required|string|max:5000', 'request_token' => 'required|uuid']);
        $record = DB::table('notary_deposit_entries')->find($entry); abort_unless($record, 404);
        DB::transaction(function () use ($entry, $data) {
            $id = $this->ledger->reverse($entry, $data, auth()->id()); $this->logCreate('Koreksi Deposit Notaris', $id);
        });
        return redirect()->route('notary-deposits.show', $record->account_id)->with('success', 'Transaksi dibalik. Saldo dan sisa tagihan sudah dihitung ulang; riwayat asli tetap tersimpan.');
    }

    public function cancel(int $account, int $bill)
    {
        $this->authorizeAction('hapus');
        DB::transaction(function () use ($account, $bill) { $this->ledger->cancelBill($account, $bill); $this->logDelete('Tagihan Notaris per Unit', $bill); });
        return redirect()->route('notary-deposits.show', $account)->with('success', 'Tagihan dibatalkan.');
    }

    public function attachment(int $entry)
    {
        $this->authorizeAction();
        $record = DB::table('notary_deposit_entries')->find($entry); abort_unless($record?->attachment, 404);
        $disk = Storage::disk('local'); abort_unless($disk->exists($record->attachment), 404);
        return response()->file($disk->path($record->attachment), ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public function statement(int $account)
    {
        $this->authorizeAction();
        return response()->view('admin.keuangan.notary_deposits.statement', $this->detail($account))->header('Cache-Control', 'private, no-store');
    }
}
