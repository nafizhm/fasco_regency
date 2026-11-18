@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper"><section class="content pt-3"><div class="container-fluid"><div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold">Pengajuan Pembelian</h3>
        @if($permissions['tambah'] ?? false)<a class="btn btn-primary btn-sm ml-auto" href="{{ route('pengajuan-pembelian.create') }}"><i class="fas fa-plus"></i> Tambah Pengajuan</a>@endif
    </div>
    <div class="card-body">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <div class="table-responsive"><table class="table table-bordered table-striped">
            <thead><tr><th>Tanggal</th><th>Nomor Pengajuan</th><th>Supplier</th><th>Keterangan</th><th>Jumlah Item</th><th>Penerimaan</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>@forelse($items as $item)<tr>
                <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td><td>{{ $item->nomor }}</td>
                <td>{{ $item->supplier?->nama ?? '-' }}</td><td>{{ $item->keterangan ?: '-' }}</td>
                <td>{{ $item->detail_count }}</td><td>{{ $item->penerimaan_count }} kali</td>
                <td><span class="badge {{ $item->masih_terpesan || !$item->detail_count ? 'badge-warning' : 'badge-success' }}">{{ $item->masih_terpesan || !$item->detail_count ? 'Masih Terpesan' : 'Sudah Diterima' }}</span></td>
                <td><a class="btn btn-info btn-sm purchase-detail" href="{{ route('pengajuan-pembelian.show', $item->id) }}" title="Lihat Detail" aria-label="Lihat Detail"><i class="fas fa-eye"></i></a></td>
            </tr>@empty<tr><td colspan="8" class="text-center">Belum ada pengajuan pembelian.</td></tr>@endforelse</tbody>
        </table></div>{{ $items->links() }}
    </div>
</div></div></section></div>
<div class="modal fade" id="purchaseDetailModal" tabindex="-1" aria-labelledby="purchaseDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="purchaseDetailTitle">Detail Pengajuan Pembelian</h5><button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div>
        <div class="modal-body" id="purchaseDetailBody"></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button></div>
    </div></div>
</div>
@endsection
@push('scripts')
<script>
    let purchaseDetailRequest;
    $(document).on('click', '.purchase-detail', function(event) {
        event.preventDefault();
        if (purchaseDetailRequest) purchaseDetailRequest.abort();
        $('#purchaseDetailTitle').text('Detail Pengajuan Pembelian ' + $(this).closest('tr').find('td').eq(1).text());
        $('#purchaseDetailBody').text('Memuat detail...');
        $('#purchaseDetailModal').modal('show');
        purchaseDetailRequest = $.ajax({ url: this.href, dataType: 'html' })
            .done(function(html) { $('#purchaseDetailBody').html(html); })
            .fail(function(xhr, status) {
                if (status !== 'abort') $('#purchaseDetailBody').text('Detail gagal dimuat. Tutup modal dan coba kembali.');
            });
    });
    $('#purchaseDetailModal').on('hidden.bs.modal', function() {
        if (purchaseDetailRequest) purchaseDetailRequest.abort();
    });
</script>
@endpush
