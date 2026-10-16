@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header"><h3>Edit SP3K</h3></section>
    <section class="content"><div class="container-fluid"><div class="card"><div class="card-body">
        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <p class="font-weight-bold">{{ $sp3k->wawancara?->customer?->nama_lengkap }}</p>
        <form method="POST" action="{{ route('acc-bank.update', $sp3k->id) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="form-row">
                <div class="form-group col-md-6"><label>Nomor SP3K</label><input class="form-control" name="no_sp3k" value="{{ old('no_sp3k', $sp3k->no_sp3k) }}" required></div>
                <div class="form-group col-md-6"><label>Tanggal SP3K</label><input class="form-control" type="date" name="tgl_terbit_sp3k" value="{{ old('tgl_terbit_sp3k', \Illuminate\Support\Carbon::parse($sp3k->tgl_terbit_sp3k)->format('Y-m-d')) }}" required></div>
                <div class="form-group col-md-6"><label>Bank KPR</label><select class="form-control" name="id_bank_kpr" required>@foreach ($banks as $bank)<option value="{{ $bank->id }}" @selected(old('id_bank_kpr', $sp3k->id_bank_kpr) == $bank->id)>{{ $bank->nama }}</option>@endforeach</select></div>
                <div class="form-group col-md-6"><label>Notaris</label><select class="form-control" name="id_notaris" required>@foreach ($notarisList as $notaris)<option value="{{ $notaris->id }}" @selected(old('id_notaris', $sp3k->id_notaris) == $notaris->id)>{{ $notaris->nama_notaris }}</option>@endforeach</select></div>
                <div class="form-group col-md-6"><label>Plafon Disetujui (Rp)</label><input class="form-control format-number" id="edit_plafon" name="acc_plafon" value="{{ old('acc_plafon', number_format($sp3k->acc_plafon, 0, ',', '.')) }}" required></div>
                <div class="form-group col-md-6"><label>Tenor (Tahun)</label><input class="form-control" type="number" min="1" name="tenor" value="{{ old('tenor', $sp3k->tenor) }}" required></div>
                <div class="form-group col-md-6"><label>Metode DP ke Bank</label><select class="form-control" id="edit_dp_mode" name="dp_mode"><option value="persentase" @selected(old('dp_mode', $sp3k->dp_mode ?? 'persentase') === 'persentase')>Persentase dari plafon SP3K</option><option value="nominal" @selected(old('dp_mode', $sp3k->dp_mode) === 'nominal')>Nominal rupiah</option></select></div>
                <div class="form-group col-md-6" id="edit_dp_percent_group"><label>DP (%)</label><input class="form-control" id="edit_dp_percent" name="dp_persen" type="number" min="0" max="100" step="0.0001" value="{{ old('dp_persen', $sp3k->dp_persen) }}"></div>
                <div class="form-group col-md-6" id="edit_dp_nominal_group"><label>DP (Rp)</label><input class="form-control format-number" id="edit_dp_nominal" name="dp_nominal" value="{{ old('dp_nominal', $sp3k->dp_nilai !== null ? number_format($sp3k->dp_nilai, 0, ',', '.') : '') }}"></div>
                <div class="form-group col-md-12"><label>Jumlah DP ke Bank</label><input class="form-control" id="edit_dp_result" readonly></div>
                <div class="form-group col-md-12"><label>Catatan</label><textarea class="form-control" name="catatan_acc">{{ old('catatan_acc', $sp3k->catatan_acc) }}</textarea></div>
                <div class="form-group col-md-12"><label>Lampiran SP3K</label>@if ($sp3k->lampiran)<p><a href="{{ asset('assets/SP3K/' . $sp3k->lampiran) }}" target="_blank" rel="noopener">Lihat lampiran saat ini</a></p>@endif<input type="file" name="lampiran" class="form-control-file" accept=".jpg,.jpeg,.png,.pdf"><small class="text-muted">Opsional untuk mengganti lampiran. Maksimal 2 MB.</small></div>
            </div>
            <p class="text-muted">Tanggal kedaluwarsa dihitung 90 hari dari tanggal SP3K. Pencairan yang sudah tercatat tetap menggunakan acuan SP3K saat transaksi dibuat.</p>
            <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
            <a class="btn btn-secondary" href="{{ route('acc-bank.index') }}">Kembali ke SP3K</a>
        </form>
    </div></div></div></section>
</div>
@endsection
@push('scripts')
<script>
$(function() {
    function previewDp() {
        const percent = $('#edit_dp_mode').val() === 'persentase';
        $('#edit_dp_percent_group').toggle(percent);
        $('#edit_dp_nominal_group').toggle(!percent);
        $('#edit_dp_percent').prop('disabled', !percent).prop('required', percent);
        $('#edit_dp_nominal').prop('disabled', percent).prop('required', !percent);
        const amount = value => Number(String(value || '').replace(/\./g, '')) || 0;
        const dp = percent ? Math.round(amount($('#edit_plafon').val()) * Number($('#edit_dp_percent').val() || 0) / 100) : amount($('#edit_dp_nominal').val());
        $('#edit_dp_result').val('Rp ' + dp.toLocaleString('id-ID'));
    }
    $('#edit_dp_mode, #edit_dp_percent, #edit_dp_nominal, #edit_plafon').on('input change', previewDp);
    previewDp();
});
</script>
@endpush
