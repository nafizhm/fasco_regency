@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <h1>{{ $dueOnly ? 'Piutang Jatuh Tempo' : 'Detail Piutang' }}</h1>
            <a href="{{ route('beranda.index') }}" class="btn btn-secondary btn-sm">Kembali ke Dashboard</a>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <h3 class="text-danger">Rp {{ number_format($customers->sum('sisa_piutang'), 0, ',', '.') }}</h3>
                    <p class="mb-0">Sisa piutang dari {{ $customers->count() }} customer aktif. Buka rincian untuk melihat tagihan dan pembayaran.</p>
                    @if($dueOnly)
                        <p class="mb-1 text-danger">Hanya piutang yang jatuh tempo hari ini dan sebelumnya (sampai {{ now('Asia/Jakarta')->format('d-m-Y') }}). Total nominal di atas sesuai data yang ditampilkan.</p>
                    @endif
                    <small class="text-muted">Pembayaran biaya ganti nama tidak diperhitungkan sebagai pembayaran piutang.</small>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="receivable-table">
                            <thead><tr><th>No</th><th>Nama</th><th>Total Tagihan</th><th>Total Pembayaran</th><th>Sisa Piutang</th><th>Jatuh Tempo</th><th>Status</th><th>Rincian</th></tr></thead>
                            <tbody>
                            @foreach ($customers as $customer)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $customer->nama_lengkap }}<br><small>{{ $customer->kavling->nama_cluster ?? '-' }} / Blok {{ $customer->kavling->kode_kavling ?? '-' }}</small></td>
                                    <td data-order="{{ $customer->total_tagihan }}" class="text-nowrap">Rp {{ number_format($customer->total_tagihan, 0, ',', '.') }}</td>
                                    <td data-order="{{ $customer->total_bayar ?? 0 }}" class="text-nowrap">Rp {{ number_format($customer->total_bayar ?? 0, 0, ',', '.') }}</td>
                                    <td data-order="{{ $customer->sisa_piutang }}" class="text-danger text-nowrap font-weight-bold">Rp {{ number_format($customer->sisa_piutang, 0, ',', '.') }}</td>
                                    <td data-order="{{ $customer->tanggal_jatuh_tempo ?? '' }}">@include('admin.dashboard.receivable-due-date')</td>
                                    <td>
                                        @if ($customer->sisa_piutang > 0)
                                            <span class="badge bg-danger">Belum Lunas</span>
                                        @else
                                            <span class="badge bg-success">Sudah Lunas</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm text-nowrap" data-toggle="modal" data-target="#receivable-modal" data-customer="{{ $customer->id }}">Lihat Rincian</button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="receivable-modal" tabindex="-1" role="dialog" aria-labelledby="receivable-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="receivable-modal-title">Rincian Piutang</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@foreach ($customers as $customer)
    <template id="receivable-detail-{{ $customer->id }}">
        <h5>{{ $customer->nama_lengkap }} <small>{{ $customer->kode_customer }}</small></h5>
        <p>{{ $customer->lokasi->nama_kavling ?? '-' }} / {{ $customer->kavling->kode_kavling ?? '-' }}</p>
        <p>Jatuh Tempo:<br>@include('admin.dashboard.receivable-due-date')</p>
        <div class="row mb-3">
            <div class="col-md-4">Total Tagihan<strong class="d-block">Rp {{ number_format($customer->total_tagihan, 0, ',', '.') }}</strong></div>
            <div class="col-md-4">Total Pembayaran<strong class="d-block">Rp {{ number_format($customer->total_bayar ?? 0, 0, ',', '.') }}</strong></div>
            <div class="col-md-4">Sisa Piutang<strong class="d-block text-danger">Rp {{ number_format($customer->sisa_piutang, 0, ',', '.') }}</strong></div>
        </div>
        <div class="table-responsive">
                                            <h6 class="mt-3">Tagihan</h6>
                                            <table class="table table-sm">
                                                <thead><tr><th>Tanggal</th><th>Deskripsi</th><th>Nominal</th></tr></thead>
                                                <tbody>
                                                @foreach ($customer->piutangs as $bill)
                                                    <tr><td class="text-nowrap">{{ $bill->tanggal_piutang ? \Carbon\Carbon::parse($bill->tanggal_piutang)->format('d-m-Y') : '-' }}</td><td>{{ $bill->deskripsi }}</td><td class="text-nowrap">Rp {{ number_format($bill->nominal, 0, ',', '.') }}</td></tr>
                                                @endforeach
                                                </tbody>
                                            </table>
        </div>
        <div class="table-responsive">
                                            <h6>Riwayat Pembayaran</h6>
                                            <table class="table table-sm">
                                                <thead><tr><th>Tanggal</th><th>Keterangan</th><th>Nominal</th></tr></thead>
                                                <tbody>
                                                @forelse ($customer->pemasukans as $payment)
                                                    <tr><td class="text-nowrap">{{ $payment->tanggal ? \Carbon\Carbon::parse($payment->tanggal)->format('d-m-Y') : '-' }}</td><td>{{ $payment->keterangan ?? '-' }}</td><td class="text-nowrap">Rp {{ number_format($payment->nominal, 0, ',', '.') }}</td></tr>
                                                @empty
                                                    <tr><td colspan="3">Belum ada pembayaran.</td></tr>
                                                @endforelse
                                                </tbody>
                                            </table>
        </div>
    </template>
@endforeach
@endsection
@push('scripts')
<script>
    $(function () {
        $('#receivable-modal').on('show.bs.modal', function (event) {
            const customerId = $(event.relatedTarget).attr('data-customer');
            const template = document.getElementById('receivable-detail-' + customerId);
            const body = $(this).find('.modal-body').empty();
            if (template) {
                body.append(template.content.cloneNode(true));
            }
        }).on('hidden.bs.modal', function () {
            $(this).find('.modal-body').empty();
        });
        $('#receivable-table').DataTable({
            order: [[4, 'desc']],
            columnDefs: [{ targets: [0, 7], orderable: false }, { targets: 7, searchable: false }],
            language: { emptyTable: 'Tidak ada piutang customer aktif.' }
        });
    });
</script>
@endpush
