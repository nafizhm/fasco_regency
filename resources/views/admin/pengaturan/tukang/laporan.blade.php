@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header"><h1 class="text-xl font-weight-bold">Laporan Tukang</h1></section>
    <section class="content"><div class="card"><div class="card-body">
        <form method="GET" action="{{ route('laporan-tukang.index') }}" class="mb-4">
            <div class="row align-items-end">
                <div class="form-group col-md-4">
                    <label for="jenis">Jenis Laporan</label>
                    <select id="jenis" name="jenis" class="form-control">
                        <option value="mingguan" @selected($type === 'mingguan')>Laporan Mingguan</option>
                        <option value="bulanan" @selected($type === 'bulanan')>Laporan Pembayaran dalam 1 Bulan</option>
                    </select>
                </div>
                <div class="form-group col-md-4" id="pilih-minggu" @if($type !== 'mingguan') hidden @endif>
                    <label for="minggu">Pilih Tanggal dalam Pekan</label>
                    <input type="date" id="minggu" name="minggu" value="{{ $week }}" class="form-control" @disabled($type !== 'mingguan') required>
                </div>
                <div class="form-group col-md-4" id="pilih-bulan" @if($type !== 'bulanan') hidden @endif>
                    <label for="bulan">Bulan Pembayaran</label>
                    <input type="month" id="bulan" name="bulan" value="{{ $month }}" class="form-control" @disabled($type !== 'bulanan') required>
                </div>
                <div class="form-group col-md-4"><button type="submit" name="preview" value="1" class="btn btn-primary">Tampilkan Preview</button></div>
            </div>
        </form>
        @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
        @if($report)
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-2">Preview Laporan</h2>
                <a class="btn btn-danger mb-2" target="_blank" rel="noopener" href="{{ route('laporan-tukang.index', ['jenis' => $type, 'minggu' => $type === 'mingguan' ? $week : null, 'bulan' => $type === 'bulanan' ? $month : null, 'format' => 'pdf']) }}"><i class="fas fa-file-pdf" aria-hidden="true"></i> Cetak PDF</a>
            </div>
            <div class="table-responsive">@include('admin.pengaturan.tukang.laporan-table')</div>
        @else
            <p class="text-muted mb-0">Pilih jenis dan periode laporan, lalu klik Tampilkan Preview.</p>
        @endif
    </div></div></section>
</div>
@endsection
@push('scripts')
<script>
document.getElementById('jenis').addEventListener('change', function() {
    const weekly = this.value === 'mingguan';
    document.getElementById('pilih-minggu').hidden = !weekly;
    document.getElementById('minggu').disabled = !weekly;
    document.getElementById('pilih-bulan').hidden = weekly;
    document.getElementById('bulan').disabled = weekly;
});
</script>
@endpush
