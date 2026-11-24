@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper"><section class="content pt-3"><div class="container-fluid"><div class="card">
    <div class="card-header"><h3 class="card-title font-weight-bold">Purchase Order {{ $data->nomor }}</h3></div>
    <div class="card-body">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        @include('admin.pengajuan_pembelian.detail')
        <a href="{{ route('pengajuan-pembelian.index') }}" class="btn btn-secondary mt-2">Kembali</a>
    </div>
</div></div></section></div>
@endsection
