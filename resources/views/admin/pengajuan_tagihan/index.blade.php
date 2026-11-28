@extends('admin.layout_admin')
@section('content')
@php($locations = \App\Models\PengajuanTagihan::LOKASI)
<div class="content-wrapper"><section class="content pt-3"><div class="container-fluid"><div class="card">
    <div class="card-header"><h3 class="card-title">Pengajuan Tagihan</h3></div>
    <div class="card-body">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        <div class="row align-items-end mb-3">
            <div class="col-sm-4"><label for="statusFilter">Filter status</label><select id="statusFilter" class="form-control"><option value="">Semua status</option>@foreach(\App\Models\PengajuanTagihan::STATUS as $status)<option value="{{ $status }}">{{ $status }}</option>@endforeach</select></div>
            <div class="col-sm-8 text-sm-right mt-2">@if($permissions['tambah'] ?? false)<a class="btn btn-primary open-tagihan" href="{{ route('pengajuan-tagihan.create') }}">Tambah Pengajuan</a>@endif <button type="button" id="printTagihan" class="btn btn-secondary"><i class="fas fa-print"></i> Cetak PDF</button></div>
        </div>
        <table id="tagihanTable" class="table table-bordered table-striped w-100">
            <thead><tr><th>Nomor / Tanggal pengajuan</th><th>Status</th><th>Nama</th><th>Uraian</th>@foreach($locations as $label)<th>{{ $label }} (Rp)</th>@endforeach<th>Total (Rp)</th><th>No rekening</th><th>Aksi</th></tr></thead>
            <tbody>
            @foreach($items as $item)
                @foreach($item->detail as $line)
                <tr data-line-id="{{ $line->id }}">
                    <td data-order="{{ $item->tanggal }}"><strong>{{ $item->nomor }}</strong><br><small class="text-muted">{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</small></td>
                    <td>{{ $item->status }}</td>
                    <td>{{ $line->nama }}</td><td style="white-space:pre-line;min-width:180px">{{ $line->uraian }}</td>
                    @foreach($locations as $key => $label)<td class="text-right text-nowrap" data-order="{{ $line->$key }}">{{ number_format($line->$key, 0, ',', '.') }}</td>@endforeach
                    <td class="text-right text-nowrap" data-order="{{ $line->total }}">{{ number_format($line->total, 0, ',', '.') }}</td>
                    <td style="min-width:180px">{{ $line->rekening ?: '-' }}</td>
                    <td class="text-nowrap">
                        @if($line->lampiran)<a class="btn btn-info btn-sm view-tagihan-attachment" data-filename="{{ $line->nama_lampiran }}" data-extension="{{ strtolower(pathinfo($line->nama_lampiran ?? '', PATHINFO_EXTENSION)) }}" data-download="{{ route('pengajuan-tagihan.attachment', [$item->id, $line->id]) }}" href="{{ route('pengajuan-tagihan.attachment', ['id' => $item->id, 'line' => $line->id, 'view' => 1]) }}" title="{{ $line->nama_lampiran }}">View Lampiran</a>@else<span class="text-muted">Tanpa lampiran</span>@endif
                        @if($permissions['edit'] ?? false)<a class="btn btn-warning btn-sm open-tagihan" href="{{ route('pengajuan-tagihan.edit', ['pengajuan_tagihan' => $item->id, 'detail' => $line->id]) }}">Edit</a>@endif
                        @if($permissions['hapus'] ?? false)<form class="d-inline" method="POST" action="{{ route('pengajuan-tagihan.destroy', ['pengajuan_tagihan' => $item->id, 'detail' => $line->id]) }}" onsubmit="return confirm('Hapus tagihan ini beserta lampirannya?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Hapus</button></form>@endif
                    </td>
                </tr>
                @endforeach
            @endforeach
            </tbody>
            <tfoot><tr><th colspan="4" class="text-right">TOTAL (Rp)</th>@foreach($locations as $key => $label)<th class="text-right total-money"></th>@endforeach<th class="text-right total-money"></th><th colspan="2"></th></tr></tfoot>
        </table>
    </div>
</div></div></section></div>
@include('admin.pengajuan_tagihan.modal')
<div class="modal fade" id="tagihanAttachmentModal" tabindex="-1" role="dialog" aria-labelledby="tagihanAttachmentTitle">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="tagihanAttachmentTitle">Lampiran</h5><button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div>
        <div class="modal-body" id="tagihanAttachmentBody"></div>
        <div class="modal-footer"><a id="tagihanAttachmentDownload" class="btn btn-primary">Unduh Lampiran</a><button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button></div>
    </div></div>
</div>
@endsection
@push('scripts')
<script>
$(function () {
 $(document).on('click', '.view-tagihan-attachment', function (event) {
  event.preventDefault();
  const body = document.getElementById('tagihanAttachmentBody');
  body.replaceChildren();
  document.getElementById('tagihanAttachmentTitle').textContent = this.dataset.filename || 'Lampiran';
  document.getElementById('tagihanAttachmentDownload').href = this.dataset.download;
  if (['jpg', 'jpeg', 'png', 'webp'].includes(this.dataset.extension)) {
   const img = document.createElement('img');
   img.src = this.href; img.alt = this.dataset.filename; img.className = 'img-fluid d-block mx-auto';
   img.onerror = () => { body.textContent = 'Lampiran gagal ditampilkan. Gunakan tombol Unduh Lampiran.'; };
   body.append(img);
  } else if (this.dataset.extension === 'pdf') {
   const frame = document.createElement('iframe');
   frame.src = this.href; frame.title = this.dataset.filename || 'Lampiran PDF';
   frame.style.cssText = 'width:100%;height:70vh;border:0'; body.append(frame);
  } else {
   body.textContent = 'Pratinjau tidak tersedia untuk file ini. Gunakan tombol Unduh Lampiran untuk membukanya.';
  }
  $('#tagihanAttachmentModal').modal('show');
 });
 $('#tagihanAttachmentModal').on('hidden.bs.modal', function () {
  document.getElementById('tagihanAttachmentBody').replaceChildren();
  document.getElementById('tagihanAttachmentDownload').removeAttribute('href');
 });
 const fmt = new Intl.NumberFormat('id-ID');
 const table = $('#tagihanTable').DataTable({
  pageLength: 25, lengthMenu: [10, 25, 50, 100], order: [], scrollX: true,
  columnDefs: [{targets: -1, orderable: false, searchable: false}],
  language: {
   search: 'Cari:', lengthMenu: 'Tampilkan _MENU_ data',
   info: 'Menampilkan _START_ - _END_ dari _TOTAL_ tagihan', infoEmpty: 'Belum ada tagihan',
   infoFiltered: '(disaring dari _MAX_ tagihan)', emptyTable: 'Belum ada pengajuan tagihan.',
   zeroRecords: 'Tidak ada tagihan yang sesuai.',
   paginate: {first: 'Pertama', last: 'Terakhir', next: 'Berikutnya', previous: 'Sebelumnya'}
  },
  footerCallback: function () {
   const api = this.api();
   for (let col = 4; col <= 9; col++) {
    const sum = api.column(col, {search: 'applied'}).nodes().toArray().reduce((total, cell) => total + BigInt(cell.dataset.order || '0'), 0n);
    $(api.column(col).footer()).text(fmt.format(sum));
   }
  }
 });
 $('#printTagihan').on('click', function () {
  const form = document.createElement('form');
  form.method = 'POST'; form.action = @json(route('pengajuan-tagihan.pdf')); form.target = '_blank';
  const field = (name, value) => { const input = document.createElement('input'); input.type = 'hidden'; input.name = name; input.value = value; form.append(input); };
  field('_token', @json(csrf_token()));
  field('status', $('#statusFilter').val()); field('search', table.search());
  table.rows({search: 'applied', order: 'applied'}).nodes().toArray().forEach(row => field('ids[]', row.dataset.lineId));
  document.body.append(form); form.submit(); form.remove();
 });
 $('#statusFilter').on('change', function () {
  const value = $.fn.dataTable.util.escapeRegex(this.value);
  table.column(1).search(value ? '^' + value + '$' : '', true, false).draw();
 });
});
</script>
@endpush
