@extends('admin.layout_admin')
@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header p-3">
                                <div class="d-flex align-content-center justify-content-between">
                                    <h3 class="font-weight-bold text-lg">Riwayat Mutasi Stok</h3>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <select id="filter_jenis" class="form-control select-jenis">
                                            <option value="">Semua Jenis</option>
                                            <option value="MASUK">MASUK</option>
                                            <option value="KELUAR">KELUAR</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <select id="filter_barang" class="form-control select-barang">
                                            <option value="">Semua Barang</option>
                                            @foreach ($barangList as $barang)
                                                <option value="{{ $barang->id }}">{{ $barang->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered data-table w-100">
                                        <thead>
                                            <tr class="bg-warning">
                                                <th width="30px">No</th>
                                                <th>Tanggal</th>
                                                <th>Jenis</th>
                                                <th>Barang</th>
                                                <th>Qty</th>
                                                <th>Referensi</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.select-jenis').select2({
                theme: "bootstrap4",
                placeholder: "Semua Jenis",
                minimumResultsForSearch: Infinity
            });

            $('.select-barang').select2({
                theme: "bootstrap4",
                placeholder: "Semua Barang",
                allowClear: true,
            });
        });

        $(function() {
            var table = $('.data-table').DataTable({
                processing: false,
                serverSide: true,
                ordering: false,
                responsive: true,
                ajax: {
                    url: "{{ route('riwayat-mutasi.index') }}",
                    data: function(d) {
                        d.id_barang = $('#filter_barang').val();
                        d.jenis = $('#filter_jenis').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center' },
                    { data: 'tanggal', name: 'tanggal' },
                    { data: 'jenis', name: 'jenis', className: 'text-center' },
                    { data: 'nama_barang', name: 'nama_barang' },
                    { data: 'qty', name: 'qty', className: 'text-center' },
                    { data: 'ref', name: 'ref' },
                ]
            });

            $('#filter_barang, #filter_jenis').on('change', function() {
                table.ajax.reload();
            });
        });
    </script>
@endpush
