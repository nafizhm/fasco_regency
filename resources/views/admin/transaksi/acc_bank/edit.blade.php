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
