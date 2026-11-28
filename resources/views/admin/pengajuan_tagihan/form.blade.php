@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper"><section class="content pt-3"><div class="container-fluid">
<a href="{{ route('pengajuan-tagihan.index') }}" class="btn btn-secondary">Kembali ke Pengajuan Tagihan</a>
<div class="modal fade" id="tagihanModal" tabindex="-1" role="dialog" aria-label="Form Pengajuan Tagihan"><div class="modal-dialog modal-lg modal-dialog-scrollable" role="document"><div class="modal-content">@include('admin.pengajuan_tagihan.form_content')</div></div></div>
</div></section></div>
@endsection
@push('scripts')
<script>$(function () { $('#tagihanModal').modal('show'); });</script>
@endpush
