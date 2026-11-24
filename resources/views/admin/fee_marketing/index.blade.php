@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper">
<section class="content-header"><h1>Fee Marketing</h1></section>
<section class="content"><div class="card"><div class="card-body">
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="d-flex justify-content-between flex-wrap mb-3">
<form method="GET" class="form-inline"><input name="q" value="{{ request('q') }}" class="form-control mr-2" placeholder="Cari nomor / penerima / unit" aria-label="Cari Fee Marketing"><button class="btn btn-outline-primary">Cari</button></form>
@if($permissions['tambah'] ?? false)<a class="btn btn-primary" href="{{ route('fee-marketing.create') }}">Tambah Fee Marketing</a>@endif
</div>
<div class="table-responsive"><table class="table table-bordered table-striped">
<thead><tr><th>Nomor / Tanggal</th><th>Leader / Tim</th><th>Nama User / Blok</th><th>Keterangan Pembayaran</th><th>Jumlah</th><th>Aksi</th></tr></thead>
<tbody>@forelse($records as $record)
<tr><td>{{ $record->nomor }}<br>{{ $record->tanggal->format('d/m/Y') }}</td><td>{{ $record->data['leader_tim'] }}</td><td>{{ $record->data['nama_user'] }}<br>{{ $record->data['blok_skema'] }}</td>
<td>{{ $record->data['untuk_pembayaran'] ?? '-' }}</td>
<td class="text-right text-nowrap">Rp {{ number_format($record->jumlah, 0, ',', '.') }}</td>
<td><div class="d-flex flex-wrap">
<a class="btn btn-sm btn-info mr-1 mb-1" href="{{ route('fee-marketing.show', $record) }}">Detail</a>
<a class="btn btn-sm btn-outline-primary mr-1 mb-1" href="{{ route('fee-marketing.pdf', $record) }}" target="_blank" rel="noopener">Cetak PDF</a>
@if($permissions['edit'] ?? false)<a class="btn btn-sm btn-primary mr-1 mb-1" href="{{ route('fee-marketing.edit', $record) }}">Edit</a>@endif
@if($permissions['hapus'] ?? false)<form method="POST" action="{{ route('fee-marketing.destroy', $record) }}" onsubmit="return confirm('Hapus bukti Fee Marketing ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">Hapus</button></form>@endif
</div></td></tr>
@empty<tr><td colspan="6" class="text-center">Belum ada Fee Marketing.</td></tr>@endforelse</tbody></table></div>
{{ $records->links('pagination::bootstrap-4') }}
</div></div></section></div>
@endsection

