@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper"><section class="content-header"><h1>Invoice IPL</h1></section><section class="content"><div class="card"><div class="card-body">
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row mb-3">
    <div class="col-md-6"><div class="border rounded p-3 mb-2 bg-light"><div class="text-muted">Total Tagihan</div><div class="h4 font-weight-bold mb-0">Rp {{ number_format($items->sum('nominal_tahun'), 0, ',', '.') }}</div></div></div>
    <div class="col-md-6"><div class="border rounded p-3 mb-2 bg-light"><div class="text-muted">Total Sisa Tagihan</div><div class="h4 font-weight-bold text-danger mb-0">Rp {{ number_format($items->sum('sisa'), 0, ',', '.') }}</div></div></div>
</div>
@if($permissions['tambah'] ?? false)<a class="btn btn-primary mb-3" href="{{ route('invoice-ipl.create') }}">Tambah Invoice IPL</a>@endif
<div class="table-responsive"><table id="iplTable" class="table table-striped table-bordered"><thead><tr><th>Nomor invoice</th><th>Nama</th><th>Alamat rumah</th><th>Tanggal STU</th><th>Nominal / tahun (Rp)</th><th>Jatuh tempo</th><th>Total pembayaran (Rp)</th><th>Sisa (Rp)</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@foreach($items as $item)<tr><td>{{ $item->nomor }}</td><td>{{ $item->nama }}</td><td>{{ $item->alamat }}</td><td>{{ $item->tanggal_stu }}</td><td class="text-right" data-order="{{ $item->nominal_tahun }}">{{ number_format($item->nominal_tahun,0,',','.') }}</td><td>{{ $item->jatuh_tempo }}</td><td class="text-right" data-order="{{ $item->total_pembayaran }}">{{ number_format($item->total_pembayaran,0,',','.') }}</td><td class="text-right" data-order="{{ $item->sisa }}">{{ number_format($item->sisa,0,',','.') }}</td><td>{{ $item->status }}</td><td class="text-nowrap">
<a class="btn btn-info btn-sm" href="{{ route('invoice-ipl.show',$item->id) }}">Detail</a> <a class="btn btn-secondary btn-sm" target="_blank" rel="noopener" href="{{ route('invoice-ipl.pdf',$item->id) }}">Cetak PDF</a>
@if($permissions['edit'] ?? false)<a class="btn btn-warning btn-sm" href="{{ route('invoice-ipl.edit',$item->id) }}">Edit</a>@endif
@if($permissions['hapus'] ?? false)<form class="d-inline" method="POST" action="{{ route('invoice-ipl.destroy',$item->id) }}" onsubmit="return confirm('Hapus invoice beserta riwayat pembayarannya?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Hapus</button></form>@endif
</td></tr>@endforeach
</tbody></table></div></div></div></section></div>
@endsection
@push('scripts')
<script>$(function() { $('#iplTable').DataTable({pageLength:25,order:[],columnDefs:[{targets:-1,orderable:false,searchable:false}],language:{search:'Cari:',lengthMenu:'Tampilkan _MENU_ data',info:'Menampilkan _START_ - _END_ dari _TOTAL_ invoice',emptyTable:'Belum ada invoice IPL.',zeroRecords:'Tidak ada invoice yang sesuai.',infoEmpty:'Belum ada invoice',paginate:{next:'Berikutnya',previous:'Sebelumnya'}}}); });</script>
@endpush
