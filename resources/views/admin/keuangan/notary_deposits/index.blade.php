@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header"><div class="container-fluid"><h1 class="text-lg font-weight-bold">Deposit Notaris</h1><p class="text-muted mb-0">Kelola saldo titipan dan tagihan unit per notaris dan lokasi perumahan.</p></div></section>
    <section class="content"><div class="container-fluid">
        @include('admin.keuangan.notary_deposits.alerts')
        <div class="alert alert-light border"><i class="fas fa-info-circle mr-1" aria-hidden="true"></i> Pencatatan ini terpisah dari Pengeluaran dan saldo rekening perusahaan. Deposit menambah saldo titipan; pemakaian deposit mengurangi saldo dan sisa tagihan unit.</div>
        <div class="row"><div class="col-lg-8">
            <div class="card"><div class="card-body">
                <form method="GET" class="row align-items-end mb-3">
                    <div class="form-group col-md-5"><label for="filter-notary">Notaris</label><select id="filter-notary" name="notary" class="form-control notary-select"><option value="">Semua notaris</option>@foreach ($notaries as $notary)<option value="{{ $notary->id }}" @selected((string) request('notary') === (string) $notary->id)>{{ $notary->nama_notaris }}</option>@endforeach</select></div>
                    <div class="form-group col-md-5"><label for="filter-location">Lokasi</label><select id="filter-location" name="location" class="form-control notary-select"><option value="">Semua lokasi</option>@foreach ($locations as $location)<option value="{{ $location->id }}" @selected((string) request('location') === (string) $location->id)>{{ $location->nama_kavling }}</option>@endforeach</select></div>
                    <div class="form-group col-md-2"><button class="btn btn-primary btn-block">Tampilkan</button></div>
                </form>
                <div class="table-responsive"><table class="table table-bordered table-hover"><thead><tr><th>Notaris</th><th>Lokasi Perumahan</th><th class="text-right">Saldo Deposit</th><th></th></tr></thead><tbody>
                    @forelse ($accounts as $account)<tr><td>{{ $account->nama_notaris }}</td><td>{{ $account->nama_kavling }}</td><td class="text-right text-nowrap font-weight-bold">Rp {{ number_format($account->balance, 0, ',', '.') }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('notary-deposits.show', $account->id) }}">Kelola</a></td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-4">Belum ada rekening deposit yang sesuai. Tambahkan rekening untuk mulai mencatat.</td></tr>@endforelse
                </tbody></table></div>
            </div></div>
        </div><div class="col-lg-4">
            @if ($canAdd)<div class="card card-outline card-primary"><div class="card-header"><h2 class="card-title font-weight-bold">Tambah Rekening Deposit</h2></div><form method="POST" action="{{ route('notary-deposits.store') }}">@csrf<div class="card-body">
                <div class="form-group"><label for="notary-id">Notaris</label><select id="notary-id" name="notary_id" class="form-control notary-select" required><option value="">Pilih notaris</option>@foreach ($notaries as $notary)<option value="{{ $notary->id }}" @selected((string) old('notary_id') === (string) $notary->id)>{{ $notary->nama_notaris }}</option>@endforeach</select></div>
                <div class="form-group"><label for="location-id">Lokasi Perumahan</label><select id="location-id" name="location_id" class="form-control notary-select" required><option value="">Pilih lokasi</option>@foreach ($locations as $location)<option value="{{ $location->id }}" @selected((string) old('location_id') === (string) $location->id)>{{ $location->nama_kavling }}</option>@endforeach</select></div>
                <p class="text-muted small mb-0">Satu rekening untuk setiap pasangan notaris dan lokasi. Saldo awal atau setoran dicatat setelah rekening dibuat.</p>
            </div><div class="card-footer"><button class="btn btn-primary btn-block">Buat Rekening Deposit</button></div></form></div>@endif
        </div></div>
    </div></section>
</div>
@endsection
@push('scripts')<script>$(function() { $('.notary-select').select2({theme:'bootstrap4', width:'100%'}); });</script>@endpush
