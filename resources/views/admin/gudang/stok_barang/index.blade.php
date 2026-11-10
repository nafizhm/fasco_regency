@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header"><div class="container-fluid"><h1>Stok Barang</h1></div></section>
    <section class="content"><div class="container-fluid"><div class="card">
        <div class="card-header"><strong>Total Nilai Stok: Rp {{ number_format($value, 0, ',', '.') }}</strong></div>
        <div class="card-body">
            <form method="get" class="form-inline mb-3"><label for="stock-search" class="mr-2">Cari barang</label><input id="stock-search" name="q" value="{{ request('q') }}" class="form-control mr-2" placeholder="Nama / SKU"><button class="btn btn-primary">Cari</button></form>
            <div class="table-responsive"><table class="table table-bordered table-striped">
                <thead><tr><th>SKU</th><th>Barang</th><th>Satuan</th><th>Stok Awal</th><th>Stok Minimal</th><th>Stok Saat Ini</th><th>Harga Beli</th><th>Nilai Stok</th></tr></thead>
                <tbody>@forelse($items as $item)<tr>
                    <td>{{ $item->sku }}</td><td>{{ $item->nama }}</td><td>{{ $item->satuan?->nama_satuan ?: '-' }}</td>
                    <td>{{ number_format($item->stok_awal, 0, ',', '.') }}</td><td>{{ number_format($item->stok_minimal, 0, ',', '.') }}</td>
                    <td><span class="badge {{ $item->stok <= $item->stok_minimal ? 'badge-danger' : 'badge-success' }}">{{ number_format($item->stok, 0, ',', '.') }}</span></td>
                    <td class="text-right">Rp {{ number_format($item->harga_beli, 0, ',', '.') }}</td><td class="text-right">Rp {{ number_format($item->stok * $item->harga_beli, 0, ',', '.') }}</td>
                </tr>@empty<tr><td colspan="8" class="text-center">Belum ada data barang.</td></tr>@endforelse</tbody>
            </table></div>
            {{ $items->links() }}
        </div>
    </div></div></section>
</div>
@endsection
