@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper"><section class="content-header"><h1>Invoice IPL {{ $data->nomor }}</h1></section><section class="content"><div class="card"><div class="card-body">
<h4>BUKTI PEMBAYARAN IURAN PENGELOLAAN LINGKUNGAN (IPL)</h4>
@include('admin.invoice_ipl.detail')
<a class="btn btn-secondary" href="{{ route('invoice-ipl.index') }}">Kembali</a> <a class="btn btn-primary" target="_blank" rel="noopener" href="{{ route('invoice-ipl.pdf',$data->id) }}">Cetak PDF</a>
@if($permissions['edit'] ?? false)<a class="btn btn-warning" href="{{ route('invoice-ipl.edit',$data->id) }}">Edit</a>@endif
</div></div></section></div>
@endsection
