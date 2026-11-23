@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper"><section class="content-header"><h1>Detail Fee Marketing</h1></section>
<section class="content">
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card"><div class="card-body">
<div class="mb-3"><a class="btn btn-secondary" href="{{ route('fee-marketing.index') }}">Kembali</a>
<a class="btn btn-primary" href="{{ route('fee-marketing.pdf', $record) }}" target="_blank" rel="noopener">Cetak / Unduh PDF</a></div>
<dl class="row">
<dt class="col-sm-3">Nomor / Tanggal</dt><dd class="col-sm-9">{{ $record->nomor }} / {{ $record->tanggal->format('d/m/Y') }}</dd>
@foreach(['diterima_dari'=>'Sudah Terima Dari', 'leader_tim'=>'Nama Leader / Tim', 'untuk_pembayaran'=>'Untuk Pembayaran', 'blok_skema'=>'Blok / Skema', 'nama_user'=>'Nama User'] as $field=>$label)
<dt class="col-sm-3">{{ $label }}</dt><dd class="col-sm-9">{{ $record->data[$field] }}</dd>
@endforeach
<dt class="col-sm-3">Jumlah</dt><dd class="col-sm-9 font-weight-bold">Rp {{ number_format($record->jumlah,0,',','.') }}</dd>
</dl>
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Blok</th><th>Level</th><th>Harga Deal</th><th>BLT</th><th>%</th><th>Komisi</th><th>Total Fee</th><th>Fee Sebelumnya</th><th>Fee Saat Ini</th></tr></thead><tbody>
@foreach($record->data['rincian'] as $row)<tr><td>{{ $row['blok'] }}</td><td>{{ $row['level'] }}</td>
@foreach(['harga_deal','blt','persen','komisi','total_fee','fee_sebelumnya','fee_saat_ini'] as $field)<td class="text-right">{{ $field === 'persen' ? $row[$field].'%' : number_format($row[$field],0,',','.') }}</td>@endforeach
</tr>@endforeach
</tbody></table></div>
<h5>Penerimaan Fee Sebelumnya</h5><table class="table table-bordered"><thead><tr><th>Tanggal</th><th>Keterangan</th><th>Nominal</th></tr></thead><tbody>
@forelse($record->data['riwayat'] as $row)<tr><td>{{ \Carbon\Carbon::parse($row['tanggal'])->format('d/m/Y') }}</td><td>{{ $row['keterangan'] }}</td><td>Rp {{ number_format($row['nominal'],0,',','.') }}</td></tr>@empty<tr><td colspan="3">Belum ada riwayat.</td></tr>@endforelse
</tbody></table>
</div></div></section></div>
@endsection

