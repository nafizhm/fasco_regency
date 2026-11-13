@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header"><h1 class="text-xl font-weight-bold">Surat Perintah Kerja (SPK)</h1></section>
    <section class="content"><div class="card"><div class="card-body">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <div class="d-flex flex-wrap justify-content-between mb-3">
            <form class="form-inline" method="GET">
                <input class="form-control mr-2 mb-2" name="q" value="{{ request('q') }}" placeholder="Nomor, pemborong, proyek" aria-label="Cari SPK">
                <select class="form-control mr-2 mb-2" name="status" aria-label="Status pembayaran">
                    <option value="">Semua pembayaran</option>
                    <option value="belum" @selected(request('status') === 'belum')>Belum Dibayar</option>
                    <option value="sebagian" @selected(request('status') === 'sebagian')>Terbayar Sebagian</option>
                    <option value="lunas" @selected(request('status') === 'lunas')>Lunas</option>
                </select>
                <button class="btn btn-outline-primary mb-2">Cari</button>
            </form>
            @if($permissions['tambah'])<a class="btn btn-primary mb-2" href="{{ route('spk.create') }}"><i class="fas fa-plus"></i> Tambah SPK</a>@endif
        </div>
        <div class="table-responsive"><table class="table table-bordered table-striped">
            <thead><tr><th>No. SPK / Tanggal</th><th>Proyek / Pemborong</th><th>Nilai Borongan</th><th>Terbayar</th><th>Sisa</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse($data as $spk)
                <tr><td>{{ $spk->nomor }}<br><small>{{ $spk->tanggal->format('d/m/Y') }}</small></td>
                    <td>{{ $spk->nama_proyek }}<br><small>{{ $spk->nama_pemborong }}</small></td>
                    <td class="text-right text-nowrap">Rp {{ number_format($spk->nilai_borongan, 0, ',', '.') }}</td>
                    <td class="text-right text-nowrap">Rp {{ number_format($spk->terbayar, 0, ',', '.') }}</td>
                    <td class="text-right text-nowrap">Rp {{ number_format($spk->sisa, 0, ',', '.') }}</td>
                    <td><span class="badge badge-{{ $spk->sisa === 0 ? 'success' : ($spk->terbayar > 0 ? 'warning' : 'danger') }}">{{ $spk->status_pembayaran }}</span></td>
                    <td class="text-nowrap"><a class="btn btn-sm btn-info" href="{{ route('spk.show', $spk) }}">Detail / Bayar</a> <a class="btn btn-sm btn-outline-primary" href="{{ route('spk.word', $spk) }}">Word</a></td>
                </tr>
                @empty<tr><td colspan="7" class="text-center text-muted">Belum ada SPK.</td></tr>@endforelse
            </tbody>
        </table></div>
        {{ $data->links('pagination::bootstrap-4') }}
    </div></div></section>
</div>
@endsection
