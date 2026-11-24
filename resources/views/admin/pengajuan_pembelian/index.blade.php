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
        <div class="table-responsive"><table id="purchaseTable" class="table table-bordered table-striped w-100">
            <thead><tr><th>No.</th><th>Tanggal</th><th>Nomor Pengajuan</th><th>Supplier</th><th>Rincian Nama Barang</th><th class="text-right">Total Pengajuan (Rp)</th><th>Penerimaan</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>@foreach($items as $item)<tr>
                <td>{{ $loop->iteration }}</td>
                <td data-order="{{ $item->tanggal }}">{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td><td>{{ $item->nomor }}</td>
                <td>{{ $item->supplier?->nama ?? '-' }}</td>
                <td>@forelse($item->detail as $detail)<div>{{ $detail->barang?->nama ?? '-' }}</div>@empty-@endforelse</td>
                <td class="text-right text-nowrap" data-order="{{ $item->detail->sum(fn ($detail) => $detail->jumlah * $detail->harga) }}">{{ number_format($item->detail->sum(fn ($detail) => $detail->jumlah * $detail->harga), 0, ',', '.') }}</td>
                <td data-order="{{ $item->penerimaan_count }}">{{ $item->penerimaan_count }} kali</td>
                <td data-order="{{ $item->masih_terpesan || !$item->detail_count ? 0 : 1 }}"><span class="badge {{ $item->masih_terpesan || !$item->detail_count ? 'badge-warning' : 'badge-success' }}">{{ $item->masih_terpesan || !$item->detail_count ? 'Masih Terpesan' : 'Sudah Diterima' }}</span></td>
                <td><a class="btn btn-info btn-sm purchase-detail" href="{{ route('pengajuan-pembelian.show', $item->id) }}" title="Lihat Detail" aria-label="Lihat Detail"><i class="fas fa-eye"></i></a>
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('pengajuan-pembelian.pdf', $item->id) }}" target="_blank" rel="noopener" title="Cetak PDF" aria-label="Cetak PDF"><i class="fas fa-file-pdf"></i> Cetak PDF</a></td>
            </tr>@endforeach</tbody>
        </table></div>
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
    $(function() {
        const table = $('#purchaseTable').DataTable({
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            order: [],
            orderFixed: { pre: [[7, 'asc']] },
            columnDefs: [{ targets: [0, 8], orderable: false, searchable: false }],
            language: {
                search: 'Cari:', lengthMenu: 'Tampilkan _MENU_ data',
                info: 'Menampilkan _START_–_END_ dari _TOTAL_ pengajuan',
                infoEmpty: 'Belum ada pengajuan pembelian', infoFiltered: '(disaring dari _MAX_ pengajuan)',
                emptyTable: 'Belum ada pengajuan pembelian.', zeroRecords: 'Tidak ada pengajuan yang sesuai.',
                paginate: { first: 'Pertama', last: 'Terakhir', next: 'Berikutnya', previous: 'Sebelumnya' }
            }
        });
        table.on('draw.dt', function() {
            const start = table.page.info().start;
            table.column(0, { page: 'current' }).nodes().each(function(cell, i) { cell.textContent = start + i + 1; });
        }).draw();
    });
    let purchaseDetailRequest;
    $(document).on('click', '.purchase-detail', function(event) {
        event.preventDefault();
        if (purchaseDetailRequest) purchaseDetailRequest.abort();
        $('#purchaseDetailTitle').text('Detail Pengajuan Pembelian ' + $(this).closest('tr').find('td').eq(2).text());
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
