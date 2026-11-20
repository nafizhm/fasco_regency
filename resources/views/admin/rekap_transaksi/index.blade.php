@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header"><h1 class="text-xl font-weight-bold">Rekap Transaksi</h1></section>
    <section class="content"><div class="card"><div class="card-body">
        <form method="GET" action="{{ route('rekap-transaksi.index') }}" class="mb-4">
            <div class="row align-items-end">
                <div class="form-group col-md-3"><label for="tanggal_awal">Tanggal Awal</label><input type="date" class="form-control" id="tanggal_awal" name="tanggal_awal" value="{{ $start }}" required></div>
                <div class="form-group col-md-3"><label for="tanggal_akhir">Tanggal Akhir</label><input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir" value="{{ $end }}" min="{{ $start }}" required></div>
                <div class="form-group col-md-3"><label for="tampilan">Tampilan</label><select class="form-control" id="tampilan" name="tampilan"><option value="ringkasan" @selected($mode === 'ringkasan')>Ringkasan</option><option value="detail" @selected($mode === 'detail')>Detail</option></select></div>
                <div class="form-group col-md-3"><button type="submit" name="preview" value="1" class="btn btn-primary">Tampilkan Preview</button></div>
            </div>
        </form>
        @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
        @if($report)
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3"><h2 class="h5 mb-2">Preview Rekap Transaksi</h2>
                <a class="btn btn-danger mb-2" href="{{ route('rekap-transaksi.index', ['tanggal_awal' => $start, 'tanggal_akhir' => $end, 'tampilan' => $mode, 'format' => 'pdf']) }}" target="_blank" rel="noopener"><i class="fas fa-file-pdf" aria-hidden="true"></i> Cetak PDF</a>
            </div>
            <div class="table-responsive">@include('admin.rekap_transaksi.table')</div>
        @else
            <p class="text-muted mb-0">Pilih periode dan tampilan ringkasan atau detail, lalu klik Tampilkan Preview.</p>
        @endif
    </div></div></section>
</div>
@endsection
@push('scripts')
<script>
document.getElementById('tanggal_awal').addEventListener('change', function() {
    document.getElementById('tanggal_akhir').min = this.value;
});
</script>
@endpush
