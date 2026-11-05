@extends('admin.layout_admin')
@section('content')
@php
    $money = fn ($amount) => 'Rp '.number_format($amount, 0, ',', '.');
    $date = fn ($value) => \Carbon\Carbon::parse($value)->locale('id')->translatedFormat('d M Y');
    $today = now('Asia/Jakarta')->toDateString();
    $reversed = $entries->pluck('reversal_of')->filter()->all();
@endphp
<div class="content-wrapper">
    <section class="content-header"><div class="container-fluid"><a href="{{ route('notary-deposits.index') }}" class="small">&larr; Semua rekening deposit</a><h1 class="text-lg font-weight-bold mt-2">{{ $record->nama_notaris }}</h1><p class="text-muted mb-0">{{ $record->nama_kavling }} &middot; Deposit dan pemakaian per unit</p></div></section>
    <section class="content"><div class="container-fluid">
        @include('admin.keuangan.notary_deposits.alerts')
        <div class="row">
            <div class="col-md-4"><div class="small-box bg-success"><div class="inner"><p>Saldo Deposit di Notaris</p><h3 style="font-size:26px">{{ $money($balance) }}</h3></div></div></div>
            <div class="col-md-4"><div class="small-box bg-warning"><div class="inner"><p>Tagihan Belum Dibayar</p><h3 style="font-size:26px">{{ $money($outstanding) }}</h3></div></div></div>
            <div class="col-md-4"><div class="small-box bg-info"><div class="inner"><p>Tagihan Belum Lunas</p><h3 style="font-size:26px">{{ $unpaid->count() }} tagihan</h3></div></div></div>
        </div>
        <div class="d-flex flex-wrap mb-3" style="gap:8px">
            @if ($canAdd)<button class="btn btn-primary" data-toggle="modal" data-target="#notaryDepositModal"><i class="fas fa-plus mr-1" aria-hidden="true"></i> Tambah Deposit</button><button class="btn btn-outline-primary" data-toggle="modal" data-target="#notaryBillModal">Tambah Tagihan Unit</button><button class="btn btn-success" data-toggle="modal" data-target="#notaryPayModal" @disabled($unpaid->isEmpty())>Catat Pemakaian Deposit</button>@endif
            <a class="btn btn-outline-secondary" href="{{ route('notary-deposits.statement', $record->id) }}" target="_blank" rel="noopener"><i class="fas fa-print mr-1" aria-hidden="true"></i> Cetak Rekap</a>
        </div>
        <p class="small text-muted">Terpisah dari pembukuan Pengeluaran. Pembuatan tagihan tidak mengurangi deposit; gunakan Catat Pemakaian Deposit saat biaya dibayarkan oleh notaris.</p>
        <div class="card"><div class="card-header"><h2 class="card-title font-weight-bold">Tagihan per Unit</h2></div><div class="card-body">
            <div class="form-group"><label for="notary-bill-filter" class="small">Tampilkan</label><select id="notary-bill-filter" class="form-control form-control-sm" style="max-width:260px"><option value="unpaid">Belum lunas saja</option><option value="all">Semua tagihan</option><option value="paid">Lunas</option><option value="cancelled">Dibatalkan</option></select></div>
            <div class="table-responsive"><table class="table table-bordered table-sm"><thead><tr><th>Tanggal / Referensi</th><th>Unit</th><th>Rincian Biaya</th><th class="text-right">Tagihan</th><th class="text-right">Terbayar</th><th class="text-right">Sisa</th><th>Status</th><th>Aksi</th></tr></thead><tbody id="notary-bills">
            @forelse ($bills as $bill)
                @php($state = $bill->cancelled_at ? 'cancelled' : ((int) $bill->paid >= (int) $bill->amount ? 'paid' : 'unpaid'))
                <tr data-state="{{ $state }}"><td class="text-nowrap">{{ $date($bill->bill_date) }}<br><small class="text-muted">{{ $bill->reference ?: '#'.$bill->id }}</small></td><td>{{ $bill->nama_cluster }} {{ $bill->kode_kavling }}</td><td>@foreach ($bill->item_balances as $item)<div>{{ $item['name'] }}: {{ $money($item['amount']) }}<br><small class="text-muted">Terbayar {{ $money($item['paid']) }} &middot; Sisa {{ $money($item['remaining']) }}</small></div>@endforeach @if($bill->notes)<small class="text-muted d-block" style="white-space:pre-wrap">{{ $bill->notes }}</small>@endif</td><td class="text-right text-nowrap">{{ $money($bill->amount) }}</td><td class="text-right text-nowrap">{{ $money($bill->paid) }}</td><td class="text-right text-nowrap">{{ $money(max(0, $bill->amount - $bill->paid)) }}</td><td><span class="badge badge-{{ $state === 'cancelled' ? 'secondary' : ($state === 'paid' ? 'success' : ($bill->paid > 0 ? 'info' : 'warning')) }}">{{ $state === 'cancelled' ? 'Dibatalkan' : ($state === 'paid' ? 'Lunas' : ($bill->paid > 0 ? 'Sebagian' : 'Belum dibayar')) }}</span></td><td class="text-nowrap">
                    @if ($canAdd && $state === 'unpaid')<button type="button" class="btn btn-sm btn-success notary-pay-bill" data-bill="{{ $bill->id }}">Bayar dari Deposit</button>@endif
                    @if ($canDelete && !$bill->cancelled_at && (int) $bill->paid === 0)<form method="POST" class="d-inline" action="{{ route('notary-deposits.cancel', [$record->id, $bill->id]) }}" onsubmit="return confirm('Batalkan tagihan ini? Riwayat tagihan tetap tersimpan.')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Batalkan</button></form>@endif
                </td></tr>
            @empty<tr><td colspan="8" class="text-muted text-center py-4">Belum ada tagihan unit. Tambahkan rincian biaya yang perlu dibayarkan oleh notaris.</td></tr>@endforelse
            </tbody></table></div><p class="text-muted small mb-0" id="notary-no-matching-bills" hidden>Tidak ada tagihan untuk status yang dipilih.</p>
        </div></div>
        <div class="card"><div class="card-header"><h2 class="card-title font-weight-bold">Mutasi Deposit</h2><span class="float-right text-muted small">Urut tanggal transaksi, dari awal</span></div><div class="card-body table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Tanggal</th><th>Uraian / Referensi</th><th>Unit</th><th class="text-right">Masuk</th><th class="text-right">Keluar</th><th class="text-right">Saldo</th><th>Aksi</th></tr></thead><tbody>
            @forelse ($entries as $entry)<tr class="{{ in_array($entry->id, $reversed) ? 'text-muted' : '' }}"><td class="text-nowrap">{{ $date($entry->entry_date) }}</td><td><strong>{{ $entry->type === 'deposit' ? 'Deposit masuk' : ($entry->type === 'payment' ? 'Pemakaian deposit' : 'Pembalikan transaksi #'.$entry->reversal_of) }}</strong>@if(in_array($entry->id, $reversed)) <span class="badge badge-secondary">Dibalik</span>@endif<br>@if($entry->cost_name)<span>{{ $entry->cost_name }}</span><br>@endif<small>{{ $entry->reference }}</small><div style="white-space:pre-wrap">{{ $entry->notes }}</div></td><td>{{ $entry->kode_kavling ? trim($entry->nama_cluster.' '.$entry->kode_kavling) : '-' }}@if($entry->bill_id)<br><small>Tagihan #{{ $entry->bill_id }}</small>@endif</td><td class="text-right text-nowrap">{{ $entry->amount > 0 ? $money($entry->amount) : '-' }}</td><td class="text-right text-nowrap">{{ $entry->amount < 0 ? $money(-$entry->amount) : '-' }}</td><td class="text-right text-nowrap font-weight-bold">{{ $money($entry->balance) }}</td><td class="text-nowrap">
                @if($entry->attachment)<button class="btn btn-sm btn-outline-info notary-attachment" data-url="{{ route('notary-deposits.attachment', $entry->id) }}" data-name="{{ $entry->attachment_name }}">Lampiran</button>@endif
                @if($canEdit && $entry->type !== 'reversal' && !in_array($entry->id, $reversed))<button class="btn btn-sm btn-outline-danger notary-reverse" data-url="{{ route('notary-deposits.reverse', $entry->id) }}" data-id="{{ $entry->id }}">Balikkan</button>@endif
            </td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada mutasi. Catat deposit pertama atau saldo awal yang dititipkan di notaris.</td></tr>@endforelse
        </tbody></table></div></div>
    </div></section>
</div>
<div id="notary-form-state" hidden data-form="{{ $errors->any() ? old('_notary_form') : '' }}" data-bill="{{ old('bill_id') }}" data-item="{{ old('item_index') }}" data-amount="{{ old('amount') }}"></div>
@if($canAdd)
    @include('admin.keuangan.notary_deposits.forms')
@endif
@if($canEdit)
<div class="modal fade" id="notaryReverseModal" tabindex="-1" role="dialog" aria-labelledby="notaryReverseTitle" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><form method="POST" id="notary-reverse-form">@csrf<input type="hidden" name="request_token" value="{{ \Illuminate\Support\Str::uuid() }}"><div class="modal-header"><h5 id="notaryReverseTitle">Balikkan Transaksi</h5><button type="button" class="close" data-dismiss="modal" aria-label="Tutup">&times;</button></div><div class="modal-body"><p>Saldo dan pembayaran tagihan akan dikoreksi hari ini. Riwayat asli tetap tersimpan.</p><label for="notary-reverse-reason">Alasan Koreksi</label><textarea id="notary-reverse-reason" class="form-control" name="notes" maxlength="5000" rows="3" required></textarea></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button><button class="btn btn-danger">Simpan Pembalikan</button></div></form></div></div></div>
@endif
<div class="modal fade" id="notaryAttachmentModal" tabindex="-1" role="dialog" aria-labelledby="notaryAttachmentTitle" aria-hidden="true"><div class="modal-dialog modal-xl"><div class="modal-content"><div class="modal-header"><h5 id="notaryAttachmentTitle">Lampiran Deposit Notaris</h5><button type="button" class="close" data-dismiss="modal" aria-label="Tutup">&times;</button></div><div class="modal-body text-center" id="notary-attachment-content"></div><div class="modal-footer"><a id="notary-attachment-original" class="btn btn-outline-primary" target="_blank" rel="noopener">Buka Berkas Asli</a><button class="btn btn-secondary" data-dismiss="modal">Tutup</button></div></div></div></div>
@endsection
@push('scripts')<script src="{{ asset('js/notary-deposits.js').'?v='.filemtime(public_path('js/notary-deposits.js')) }}"></script>@endpush
