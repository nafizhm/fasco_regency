@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header"><h1 class="text-xl font-weight-bold">Detail SPK</h1></section>
    <section class="content"><div class="card"><div class="card-body">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
        <div class="d-flex flex-wrap mb-3">
            <a class="btn btn-secondary mr-2 mb-2" href="{{ route('spk.index') }}">Kembali</a>
            <a class="btn btn-primary mr-2 mb-2" href="{{ route('spk.word', $spk) }}"><i class="fas fa-file-word"></i> Cetak Word (.docx)</a>
            @if($permissions['edit'] && !$spk->dibatalkan_pada)<a class="btn btn-outline-primary mr-2 mb-2" href="{{ route('spk.edit', $spk) }}">Edit SPK</a>@endif
            @if($permissions['edit'] && !$spk->dibatalkan_pada && $spk->sisa > 0)<button type="button" class="btn btn-success mr-2 mb-2" data-toggle="modal" data-target="#paymentModal">Catat Pembayaran</button>@endif
            @if($permissions['edit'] && !$spk->dibatalkan_pada)
                @include('admin.spk.cancel-button')
            @endif
            @if($permissions['hapus'] && $spk->terbayar === 0)
                <form method="POST" action="{{ route('spk.destroy', $spk) }}" onsubmit="return confirm('Hapus SPK ini?')">@csrf @method('DELETE')<button class="btn btn-outline-danger mb-2">Hapus SPK</button></form>
            @endif
        </div>
        @if($spk->dibatalkan_pada)<div class="alert alert-secondary">SPK dibatalkan pada {{ $spk->dibatalkan_pada->format('d/m/Y H:i') }}. Riwayat pembayaran tetap tersimpan.</div>@endif
        <h4>{{ $spk->nomor }}</h4><p>Tanggal surat: {{ $spk->tanggal->format('d/m/Y') }}</p>
        <div class="row mb-3">
            <div class="col-md-3"><div class="border rounded p-3 h-100"><small>Nilai Borongan</small><div class="font-weight-bold">Rp {{ number_format($spk->nilai_borongan, 0, ',', '.') }}</div></div></div>
            <div class="col-md-3"><div class="border rounded p-3 h-100"><small>Sudah Terbayar</small><div class="font-weight-bold">Rp {{ number_format($spk->terbayar, 0, ',', '.') }}</div></div></div>
            <div class="col-md-3"><div class="border rounded p-3 h-100"><small>Sisa Tagihan</small><div class="font-weight-bold">Rp {{ number_format($spk->sisa, 0, ',', '.') }}</div></div></div>
            <div class="col-md-3"><div class="border rounded p-3 h-100"><small>Status Pembayaran</small><div><span class="badge badge-{{ $spk->sisa === 0 ? 'success' : ($spk->terbayar > 0 ? 'warning' : 'danger') }}">{{ $spk->status_pembayaran }}</span></div></div></div>
        </div>
        <div class="row">
            <div class="col-md-6"><h5 class="font-weight-bold">Pihak Pertama</h5><p>{{ $spk->nama_perusahaan }}<br>{{ $spk->alamat_perusahaan }}<br>Telp: {{ $spk->telp_perusahaan }}<br>Penandatangan: {{ $spk->penandatangan }} ({{ $spk->kota }})</p></div>
            <div class="col-md-6"><h5 class="font-weight-bold">Pihak Kedua</h5><p>{{ $spk->nama_pemborong }}<br>{{ $spk->jabatan_pemborong }}<br>{{ $spk->alamat_pemborong }}</p></div>
        </div>
        <h5 class="font-weight-bold">Pekerjaan</h5>
        <dl class="row">
            @foreach(['nama_proyek' => 'Nama Proyek', 'lokasi_proyek' => 'Lokasi Proyek', 'jenis_pekerjaan' => 'Jenis Pekerjaan', 'lama_pengerjaan' => 'Lama Pengerjaan', 'skema_pembayaran' => 'Skema Pembayaran'] as $field => $label)
                <dt class="col-md-3">{{ $label }}</dt><dd class="col-md-9" style="white-space:pre-line">{{ $spk->$field }}</dd>
            @endforeach
            <dt class="col-md-3">Waktu Pelaksanaan</dt><dd class="col-md-9">{{ $spk->tanggal_mulai->format('d/m/Y') }}{{ $spk->tanggal_selesai ? ' s/d '.$spk->tanggal_selesai->format('d/m/Y') : '' }}</dd>
        </dl>
        <div class="table-responsive"><table class="table table-bordered">
            <thead><tr><th>No</th><th>Item</th><th>Volume</th><th>Satuan</th><th>Harga Satuan</th><th>Total Harga</th></tr></thead>
            <tbody>@foreach($spk->items as $item)<tr><td>{{ $loop->iteration }}</td><td>{{ $item['item'] }}</td><td>{{ number_format($item['volume'], 3, ',', '.') }}</td><td>{{ $item['satuan'] }}</td><td class="text-right text-nowrap">Rp {{ number_format($item['harga_satuan'], 0, ',', '.') }}</td><td class="text-right text-nowrap">Rp {{ number_format($item['total'], 0, ',', '.') }}</td></tr>@endforeach</tbody>
            <tfoot><tr><th colspan="5" class="text-right">Total Rincian</th><th class="text-right">Rp {{ number_format($spk->total_rincian, 0, ',', '.') }}</th></tr><tr><th colspan="5" class="text-right">Dibulatkan / Nilai Borongan</th><th class="text-right">Rp {{ number_format($spk->nilai_borongan, 0, ',', '.') }}</th></tr></tfoot>
        </table></div>
        <h5 class="font-weight-bold mt-4">Riwayat Pembayaran</h5>
        <div class="table-responsive"><table class="table table-bordered table-striped">
            <thead><tr><th>Tanggal</th><th>Nominal</th><th>Metode</th><th>Referensi</th><th>Keterangan</th><th>Bukti Bayar</th></tr></thead>
            <tbody>@forelse($spk->pembayaran as $payment)<tr><td>{{ $payment->tanggal->format('d/m/Y') }}</td><td class="text-right text-nowrap">Rp {{ number_format($payment->nominal, 0, ',', '.') }}</td><td>{{ $payment->metode ?: '-' }}</td><td>{{ $payment->referensi ?: '-' }}</td><td>{{ $payment->keterangan ?: '-' }}</td><td>@if($payment->bukti_bayar)<button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#proofModal" data-url="{{ route('spk.proof', [$spk, $payment]) }}" data-pdf="{{ strtolower(pathinfo($payment->bukti_bayar, PATHINFO_EXTENSION)) === 'pdf' ? '1' : '0' }}">Lihat Bukti Bayar</button>@else - @endif</td></tr>@empty<tr><td colspan="6" class="text-center text-muted">Belum ada pembayaran.</td></tr>@endforelse</tbody>
        </table></div>
    </div></div></section>
</div>
@if($permissions['edit'] && !$spk->dibatalkan_pada && $spk->sisa > 0)
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentTitle" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('spk.payment', $spk) }}" id="payment-form" enctype="multipart/form-data">
        @csrf<input type="hidden" name="token" value="{{ old('token', (string) \Illuminate\Support\Str::uuid()) }}">
        <div class="modal-header"><h5 class="modal-title" id="paymentTitle">Catat Pembayaran SPK</h5><button type="button" class="close" data-dismiss="modal" aria-label="Tutup">&times;</button></div>
        <div class="modal-body">
            <p>Sisa tagihan: <strong>Rp {{ number_format($spk->sisa, 0, ',', '.') }}</strong></p>
            <div class="form-group"><label for="bayar_tanggal">Tanggal Pembayaran</label><input class="form-control" type="date" id="bayar_tanggal" name="tanggal" value="{{ old('tanggal', now('Asia/Jakarta')->toDateString()) }}" min="{{ $spk->tanggal->format('Y-m-d') }}" required></div>
            <div class="form-group"><label for="nominal">Nominal (Rp)</label><input class="form-control" type="text" inputmode="numeric" id="nominal" value="{{ old('nominal') }}" autocomplete="off" required><input type="hidden" id="nominal_value" name="nominal" value="{{ old('nominal') }}"></div>
            <div class="form-group"><label for="metode">Metode Pembayaran</label><select class="form-control" id="metode" name="metode" required><option value="">Pilih Metode Pembayaran</option><option value="Cash" @selected(old('metode') === 'Cash')>Cash</option><option value="Transfer" @selected(old('metode') === 'Transfer')>Transfer</option></select></div>
            <div class="form-group"><label for="bukti_bayar">Bukti Bayar (opsional)</label><input type="file" class="form-control-file" id="bukti_bayar" name="bukti_bayar" accept=".jpg,.jpeg,.png,.webp,.pdf"><small class="text-muted">JPG, PNG, WEBP, atau PDF. Maksimal 2 MB.</small></div>
            <div class="form-group"><label for="referensi">Nomor Referensi (opsional)</label><input class="form-control" id="referensi" name="referensi" value="{{ old('referensi') }}" maxlength="255"></div>
            <div class="form-group"><label for="keterangan">Keterangan</label><textarea class="form-control" id="keterangan" name="keterangan">{{ old('keterangan') }}</textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button><button class="btn btn-primary" id="save-payment">Simpan Pembayaran</button></div>
    </form>
</div></div></div>
@endif
<div class="modal fade" id="proofModal" tabindex="-1" aria-labelledby="proofTitle" aria-hidden="true"><div class="modal-dialog modal-xl"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="proofTitle">Bukti Bayar SPK</h5><button type="button" class="close" data-dismiss="modal" aria-label="Tutup">&times;</button></div>
    <div class="modal-body text-center" id="proofContent"></div>
</div></div></div>
@endsection
@push('scripts')
<script>
$(function() {
    $('#proofModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const url = button.attr('data-url');
        const preview = button.attr('data-pdf') === '1'
            ? $('<iframe>', { src: url, title: 'Bukti Bayar SPK', style: 'width:100%;height:70vh;border:0' })
            : $('<img>', { src: url, alt: 'Bukti Bayar SPK', style: 'max-width:100%;max-height:75vh' });
        $('#proofContent').empty().append(preview);
    }).on('hidden.bs.modal', function() { $('#proofContent').empty(); });
    function formatNominal() {
        const input = document.getElementById('nominal');
        if (!input) return;
        const digits = input.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '');
        input.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        $('#nominal_value').val(digits);
        input.setCustomValidity(digits && Number(digits) < 1 ? 'Nominal harus lebih dari nol.' :
            (Number(digits) > {{ $spk->sisa }} ? 'Pembayaran melebihi sisa tagihan SPK.' : ''));
    }
    $('#nominal').on('input', formatNominal);
    formatNominal();
    $('#payment-form').on('submit', function() { $('#save-payment').prop('disabled', true).text('Menyimpan...'); });
    @if(old('token')) $('#paymentModal').modal('show'); @endif
});
</script>
@endpush
