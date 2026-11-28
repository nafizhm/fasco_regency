@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper"><section class="content-header"><h1>{{ $data->exists ? 'Edit' : 'Tambah' }} Rekap KPR</h1></section><section class="content"><div class="card"><div class="card-body">
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ $data->exists ? route('rekap-kpr.update',$data->id) : route('rekap-kpr.store') }}">@csrf @if($data->exists) @method('PUT') @endif
<div class="form-group row"><label for="bank" class="col-sm-3 col-form-label">Bank KPR</label><div class="col-sm-9"><input id="bank" class="form-control" name="bank" list="bankOptions" required maxlength="100" value="{{ old('bank',$data->bank) }}"><datalist id="bankOptions">@foreach($banks as $name)<option value="{{ $name }}">@endforeach</datalist><small class="form-text text-muted">Pilih bank atau ketik nama bank lainnya.</small></div></div>
@foreach(['nama_user'=>'Nama User KPR','blok'=>'Blok','tanggal_booking'=>'Tanggal Booking','tanggal_itj'=>'Tanggal ITJ','tanggal_masuk'=>'Tanggal Masuk','perumahan'=>'Perumahan'] as $field=>$label)
<div class="form-group row"><label for="{{ $field }}" class="col-sm-3 col-form-label">{{ $label }}</label><div class="col-sm-9"><input class="form-control" id="{{ $field }}" name="{{ $field }}" type="{{ str_starts_with($field,'tanggal_') ? 'date' : 'text' }}" value="{{ old($field,$data->$field) }}" @required(in_array($field,['nama_user','perumahan'])) maxlength="{{ $field==='blok' ? 100 : ($field==='perumahan' ? 150 : 255) }}"></div></div>@endforeach
<div class="form-group row"><label for="keterangan" class="col-sm-3 col-form-label">Keterangan</label><div class="col-sm-9"><textarea id="keterangan" class="form-control" name="keterangan" rows="3" maxlength="5000">{{ old('keterangan',$data->keterangan) }}</textarea></div></div>
<div class="form-group row"><div class="col-sm-9 offset-sm-3"><input type="hidden" name="tandai_merah" value="0"><label><input type="checkbox" name="tandai_merah" value="1" @checked(old('tandai_merah',$data->tandai_merah))> Tandai baris merah (contoh: pengembalian)</label></div></div>
<a class="btn btn-secondary" href="{{ route('rekap-kpr.index') }}">Kembali</a> <button class="btn btn-primary">Simpan</button>
</form></div></div></section></div>
@endsection
