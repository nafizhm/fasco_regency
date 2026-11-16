@extends('admin.layout_admin')
@section('content')
    <style>
        .payment-header { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
        .payment-header h3 { margin:0; font-size:20px; font-weight:700; line-height:1.4; }
        .payment-header p { margin:4px 0 0; color:#6c757d; font-size:13px; }
        .payment-header .btn { flex-shrink:0; white-space:nowrap; }
        .payment-filters { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; padding:16px; margin-bottom:20px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px; }
        .payment-filter { min-width:0; }
        .payment-filter label { display:block; margin-bottom:6px; font-size:13px; font-weight:600; color:#495057; }
        .payment-filter .select2-container { width:100% !important; max-width:100%; }
        .payment-filter .select2-selection--single { min-height:38px; }
        .payment-filter .select2-selection__rendered { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        @media(max-width:767.98px) { .payment-filters { grid-template-columns:minmax(0,1fr); gap:12px; } }
    </style>
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                    </div>
                </div>
            </div>
        </section>
        <div class="container-fluid">
            <div class="card">
                <div class="card-header p-3">
                    <div class="payment-header">
                        <div>
                            <h3>Data Pembayaran</h3>
                            <p>Kelola tagihan dan pembayaran customer.</p>
                        </div>
                        <a class="btn btn-info" href="{{ route('pembayaran.rekap') }}">
                            <i class="fas fa-list-alt mr-1" aria-hidden="true"></i> Rekap Pembayaran
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="payment-filters" role="group" aria-label="Filter data pembayaran">
                        <div class="payment-filter">
                            <label for="jenis_pembelian">Jenis Pembelian</label>
                            <select class="form-control select-1" name="jenis_pembelian" id="jenis_pembelian">
                                <option value="">Semua Jenis Pembelian</option>
                                <option value="Pembelian Cash">Pembelian Cash</option>
                                <option value="Cash Bertahap">Cash Bertahap</option>
                                <option value="KPR">Pembelian KPR</option>
                            </select>
                        </div>
                        <div class="payment-filter">
                            <label for="status">Status Pembayaran</label>
                            <select class="form-control select-1" name="status" id="status">
                                <option value="">Semua Status Pembayaran</option>
                                <option value="Lunas">Lunas</option>
                                <option value="Terhutang">Terhutang</option>
                            </select>
                        </div>
                        <div class="payment-filter">
                            <label for="progres">Progres Customer</label>
                            <select class="form-control select-2" name="progres" id="progres">
                                <option value="">Semua Progres Customer</option>
                                @foreach ($progreslists as $list)
                                    <option value="{{ $list->id }}">{{ $list->status_progres }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <table class="table table-bordered table-striped data-table">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="15%">Customer</th>
                                <th width="20%">Lokasi Unit</th>
                                <th width="10%">Status</th>
                                <th width="18%">Jumlah Tagihan</th>
                                <th width="10%" class="text-center">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.select-1').select2({
                theme: "bootstrap4",
                width: "100%",
                minimumResultsForSearch: Infinity,
            });
            $('.select-2').select2({
                theme: "bootstrap4",
                width: "100%",
            });
        });

        $(function() {
            var permissions = @json($permissions);
            var showActionColumn = (permissions['edit'] == 1 || permissions['hapus'] == 1);

            var table = $('.data-table').DataTable({
                processing: false,
                serverSide: false,
                responsive: true,
                ordering: false,
                ajax: {
                    url: "{{ route('pembayaran.index') }}",
                    data: function(d) {
                        d.status = $('#status').val();
                        d.progres = $('#progres').val();
                        d.jenis_pembelian = $('#jenis_pembelian').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'customer',
                        name: 'customer'
                    },
                    {
                        data: 'lokasi_unit',
                        name: 'lokasi_unit',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'jumlah_tagihan',
                        name: 'jumlah_tagihan',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        visible: showActionColumn,
                        className: 'text-center'
                    }
                ],
                columnDefs: [{
                    targets: 0,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                }]
            });

            $('#status, #progres, #jenis_pembelian').change(function() {
                table.ajax.reload();
            });
        });
    </script>
@endpush
