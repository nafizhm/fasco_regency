@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper"><section class="content-header"><h1>Detail Rekap KPR</h1></section><section class="content"><div class="card"><div class="card-body">
<table class="table table-bordered {{ $data->tandai_merah ? 'text-danger' : '' }}">
@foreach(['bank'=>'Bank KPR','nama_user'=>'Nama User','blok'=>'Blok','tanggal_booking'=>'Tanggal Booking','tanggal_itj'=>'Tanggal ITJ','tanggal_masuk'=>'Tanggal Masuk','keterangan'=>'Keterangan','perumahan'=>'Perumahan'] as $field=>$label)<tr><th style="width:25%">{{ $label }}</th><td style="white-space:pre-line">{{ str_starts_with($field,'tanggal_') && $data->$field ? \Carbon\Carbon::parse($data->$field)->format('d/m/Y') : ($data->$field ?: '-') }}</td></tr>@endforeach
</table><a class="btn btn-secondary" href="{{ route('rekap-kpr.index') }}">Kembali</a> @if($permissions['edit'] ?? false)<a class="btn btn-warning" href="{{ route('rekap-kpr.edit',$data->id) }}">Edit</a>@endif
</div></div></section></div>
@endsection
