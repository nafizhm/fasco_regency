@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper"><section class="content pt-3"><div class="container-fluid">
<h1 class="h4">Update Status Siteplan</h1>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="card"><div class="card-body">
<form method="GET" action="{{ route('siteplan-status.index') }}" class="mb-3">
<label for="id_lokasi">Lokasi</label>
<select id="id_lokasi" name="id_lokasi" class="form-control" onchange="this.form.submit()">
@foreach($lokasiOptions as $location)<option value="{{ $location->id }}" @selected($selectedLocation?->id == $location->id)>{{ $location->nama_kavling }}</option>@endforeach
</select><noscript><button class="btn btn-primary">Tampilkan</button></noscript>
</form>
<a class="btn btn-outline-primary" href="{{ route('siteplan-penjualan.index', ['id_lokasi' => $selectedLocation?->id]) }}">Lihat Siteplan</a>
</div></div>
<div class="card"><div class="card-header">Pengaturan Warna</div><div class="card-body">
<form method="POST" action="{{ route('siteplan-status.colors') }}">@csrf @method('PATCH')
@foreach($colors as $status => $color)<label class="mr-3">{{ ucfirst($status) }} <input type="color" name="{{ $status }}" value="{{ old($status, $color) }}" required></label>@endforeach
<button class="btn btn-primary btn-sm">Simpan Warna</button>
</form></div></div>
<div class="card"><div class="card-body">
<p>Status manual berlaku untuk tampilan siteplan dan cetak. Data customer dan booking tetap tersimpan.</p>
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Nama Cluster</th><th>Blok / Kavling</th><th>Status Siteplan</th><th>Update Manual</th></tr></thead><tbody>
@forelse(($selectedLocation?->kavlingPeta ?? collect())->sortBy('kode_kavling') as $unit)
<tr><td>{{ $unit->nama_cluster ?: '-' }}</td><td>{{ $unit->kode_kavling }}</td><td>{{ ucfirst(\App\Services\SiteplanColors::status($unit)) }}</td><td>
<form method="POST" action="{{ route('siteplan-status.update', $unit->id) }}" class="d-flex">@csrf @method('PATCH')
<select name="status" class="form-control form-control-sm mr-2" aria-label="Status {{ $unit->kode_kavling }}">
@foreach(['ready', 'proses', 'sold'] as $status)<option value="{{ $status }}" @selected(\App\Services\SiteplanColors::status($unit) === $status)>{{ ucfirst($status) }}</option>@endforeach
</select><button class="btn btn-primary btn-sm">Simpan</button></form></td></tr>
@empty<tr><td colspan="4">Belum ada unit.</td></tr>@endforelse
</tbody></table></div></div></div>
</div></section></div>
@endsection
