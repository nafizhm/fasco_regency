@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper"><section class="content-header"><h1>Rekap KPR</h1></section><section class="content"><div class="card"><div class="card-body">
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="d-flex justify-content-between align-items-end mb-3 flex-wrap">
<form method="GET" class="form-inline"><label for="bankFilter" class="mr-2">Bank</label><select id="bankFilter" name="bank" class="form-control mr-2"><option value="">Semua Bank</option>@foreach($banks as $name)<option value="{{ $name }}" @selected($bank===$name)>{{ $name }}</option>@endforeach</select><input class="form-control mr-2" name="q" aria-label="Cari data KPR" placeholder="Cari nama, blok, perumahan..." maxlength="255" value="{{ $q }}"><button class="btn btn-secondary">Tampilkan</button> <a class="btn btn-link" href="{{ route('rekap-kpr.index') }}">Reset</a></form>
@if($permissions['tambah'] ?? false)<a href="{{ route('rekap-kpr.create') }}" class="btn btn-primary mt-2">Tambah Rekap KPR</a>@endif</div>
<h2 class="text-center font-weight-bold mb-4">DATA USER KPR NAUFAL GROUP</h2>
@foreach($groups as $group)
<div class="table-responsive mb-4"><table class="table table-bordered rekap-kpr-table"><thead><tr><th colspan="9" class="bank-heading">USER KPR {{ $group }}</th></tr><tr><th style="width:40px">NO</th><th>USER</th><th>BLOK</th><th>BOOKING</th><th>ITJ</th><th>TGL MSK</th><th>KET</th><th>PERUMAHAN</th><th>AKSI</th></tr></thead><tbody>
@forelse($items->get($group,collect()) as $item)<tr class="{{ $item->tandai_merah ? 'text-danger font-weight-bold' : '' }}">
<td>{{ $loop->iteration }}</td><td>{{ $item->nama_user }}</td><td>{{ $item->blok ?: '-' }}</td>
@foreach(['tanggal_booking','tanggal_itj','tanggal_masuk'] as $field)<td class="text-nowrap">{{ $item->$field ? \Carbon\Carbon::parse($item->$field)->format('d/m/Y') : '-' }}</td>@endforeach
<td style="white-space:pre-line;min-width:180px">{{ $item->keterangan ?: '-' }}</td><td>{{ $item->perumahan }}</td><td class="text-nowrap">
<a class="btn btn-info btn-sm" href="{{ route('rekap-kpr.show',$item->id) }}">Detail</a>
@if($permissions['edit'] ?? false)<a class="btn btn-warning btn-sm" href="{{ route('rekap-kpr.edit',$item->id) }}">Edit</a>@endif
@if($permissions['hapus'] ?? false)<form class="d-inline" method="POST" action="{{ route('rekap-kpr.destroy',$item->id) }}" onsubmit="return confirm('Hapus data rekap KPR ini?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Hapus</button></form>@endif
</td></tr>@empty<tr><td colspan="9" class="text-center text-muted">Belum ada data KPR {{ $group }} sesuai filter.</td></tr>@endforelse
</tbody></table></div>@endforeach
</div></div></section></div>
<style>.rekap-kpr-table th,.rekap-kpr-table td{vertical-align:middle;text-align:center}.rekap-kpr-table .bank-heading{background:#b9dfe8;font-size:20px;padding:14px}.rekap-kpr-table{border-color:#777}.rekap-kpr-table thead th{white-space:nowrap}</style>
@endsection
