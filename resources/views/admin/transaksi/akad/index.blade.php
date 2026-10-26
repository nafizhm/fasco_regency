@extends('admin.layout_admin')
@section('content')
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header p-3">
                                <div class="d-flex align-content-center justify-content-between">
                                    <h3 class="font-weight-bold text-lg">Data Akad</h3>
                                    <div class="d-flex align-items-center">
                                        @if ($permissions['tambah'])
                                            <button class="btn btn-primary btn-sm" data-toggle="modal"
                                                data-target="#modalForm"><i class="fas fa-plus"></i>
                                                Tambah Akad</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table w-100 table-bordered table-striped data-table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Tanggal Akad</th>
                                            <th>Pembeli</th>
                                            <th>Unit</th>
                                            <th>Tanggal Booking</th>
                                            <th>Bank KPR</th>
                                            <th>Jenis Akad</th>
                                            <th>Status</th>
                                            <th>Keterangan</th>
                                            <th class="text-center" width="15%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                    </div>
                    <!-- /.col -->
                </div>
                <!-- /.row -->
            </div>
            <!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modalForm" tabindex="-1" role="dialog" aria-labelledby="modalFormLabel" aria-hidden="true"
        data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header bg-indigo">
                    <h5 class="modal-title text-white font-weight-bold" id="modalFormLabel">Form Akad</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formData">
                    @csrf
                    <input type="hidden" id="primary_id" name="primary_id">
                    <div class="modal-body">
                        <p class="text-muted">Catat akad yang sudah dilaksanakan untuk satu pembeli.</p>
                        <div class="form-group">
                            <label for="id_customer">Pembeli</label>
                            <select id="id_customer" name="id_customer" class="form-control" required>
                                <option value="">Pilih pembeli</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->nama_lengkap }} (#{{ $customer->id }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="jenis_akad">Jenis Akad</label>
                            <select id="jenis_akad" name="jenis_akad" class="form-control" required>
                                <option value="">Pilih jenis akad</option>
                                <option value="AJB">AJB</option><option value="PPJB">PPJB</option>
                            </select>
                        </div>
                        <div class="form-group row mb-3">
                            <label for="tgl_akad" class="col-sm-4 col-form-label">Tanggal</label>
                            <div class="col-sm-8">
                                <input type="date" id="tgl_akad" class="form-control" name="tgl_akad"
                                    required max="{{ now('Asia/Jakarta')->toDateString() }}" value="{{ now('Asia/Jakarta')->toDateString() }}">
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="keterangan" class="col-sm-4 col-form-label">Keterangan</label>
                            <div class="col-sm-8">
                                <input type="text" id="keterangan" class="form-control" name="keterangan">
                            </div>
                        </div>

                        <fieldset id="buyer-fields" disabled style="display: none;">
                            <legend class="h6">Edit Data Pembeli</legend>
                            @foreach (['nama_lengkap' => 'Nama Pembeli', 'nik' => 'NIK', 'no_telp' => 'Nomor Telepon', 'alamat_ktp' => 'Alamat', 'tgl_booking' => 'Tanggal Booking'] as $field => $label)
                                <div class="form-group">
                                    <label for="buyer-{{ $field }}">{{ $label }}</label>
                                    <input type="{{ $field === 'tgl_booking' ? 'date' : 'text' }}" class="form-control" id="buyer-{{ $field }}" name="buyer[{{ $field }}]">
                                </div>
                            @endforeach
                            <div class="form-group">
                                <label for="buyer-id_bank_kpr">Bank KPR</label>
                                <select class="form-control" id="buyer-id_bank_kpr" name="buyer[id_bank_kpr]">
                                    <option value="">Belum diisi</option>
                                    @foreach ($banks as $bank)<option value="{{ $bank->id }}">{{ $bank->nama }}</option>@endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="buyer-id_marketing">Marketing</label>
                                <select class="form-control" id="buyer-id_marketing" name="buyer[id_marketing]">
                                    <option value="">Belum diisi</option>
                                    @foreach ($marketings as $marketing)<option value="{{ $marketing->id }}">{{ $marketing->nama_marketing }}</option>@endforeach
                                </select>
                            </div>
                        </fieldset>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary " id="submitBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            <span class="button-text">Simpan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        $(function() {
            var permissions = @json($permissions);
            var showActionColumn = (permissions['edit'] == 1 || permissions['hapus'] == 1);
            var table = $('.data-table').DataTable({
                processing: false,
                serverSide: false,
                ordering: false,
                responsive: true,
                ajax: "{{ route('akad.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'tgl_akad',
                        name: 'tgl_akad',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nama_lengkap',
                        name: 'nama_lengkap',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'unit', name: 'unit'
                    },
                    {
                        data: 'tgl_booking', name: 'tgl_booking', defaultContent: 'Belum diisi'
                    },
                    {
                        data: 'bank_kpr', name: 'bank_kpr', defaultContent: 'Belum diisi'
                    },
                    {
                        data: 'jenis_akad', name: 'jenis_akad'
                    },
                    {
                        data: 'status_label', name: 'status_label'
                    },
                    {
                        data: 'keterangan',
                        name: 'keterangan',
                        orderable: false,
                        searchable: true
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
                }, ]
            });
        });

        $(document).on('click', '.edit-button', function() {
            var url = $(this).data('url');
            $.get(url, function(response) {
                if (response.status === 'success') {
                    $('#primary_id').val(response.data.id);
                    $('#id_customer').val(response.data.id_customer);
                    $('#id_customer').prop('disabled', true);
                    $('#buyer-fields').prop('disabled', false).show();
                    Object.entries(response.data.buyer || {}).forEach(([key, value]) => $('#buyer-' + key).val(value));
                    $('#tgl_akad').prop('required', !!response.data.tgl_akad);
                    $('#jenis_akad').val(response.data.jenis_akad);
                    $('#tgl_akad').val(response.data.tgl_akad);
                    $('#keterangan').val(response.data.keterangan);

                    $('#modalForm').modal('show');
                }
            });
        });

        $('#modalForm').on('hidden.bs.modal', function() {
            $('#formData')[0].reset();
            $('#primary_id').val('');
            $('#id_customer').prop('disabled', false);
            $('#buyer-fields').prop('disabled', true).hide();
            $('#tgl_akad').prop('required', true);
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            let submitBtn = $('#submitBtn');
            let spinner = submitBtn.find('.spinner-border');
            let btnText = submitBtn.find('.button-text');

            spinner.addClass('d-none');
            btnText.text('Simpan');
            submitBtn.prop('disabled', false);
        });


        $('#formData').on('submit', function(e) {
            e.preventDefault();
            let submitBtn = $('#submitBtn');
            let spinner = submitBtn.find('.spinner-border');
            let btnText = submitBtn.find('.button-text');

            spinner.removeClass('d-none');
            btnText.text('Menyimpan...');
            submitBtn.prop('disabled', true);

            let id = $('#primary_id').val();
            let url = id ? '{{ route('akad.update', ['akad' => ':id']) }}'.replace(':id', id) :
                '{{ route('akad.store') }}';
            let method = id ? 'PUT' : 'POST';

            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            let formData = new FormData(this);
            if (id) formData.set('id_customer', $('#id_customer').val());
            formData.append('_method', method);

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function() {
                    $('#modalForm').modal('hide');
                    audio.play();
                    toastr.success("Data telah disimpan!", "BERHASIL", {
                        progressBar: true,
                        timeOut: 3500,
                        positionClass: "toast-bottom-right",
                    });
                    $('.data-table').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    spinner.addClass('d-none');
                    btnText.text('Simpan');
                    submitBtn.prop('disabled', false);
                    if (xhr.status === 422) {
                        audio.play();
                        toastr.error("Ada inputan yang salah!", "GAGAL!", {
                            progressBar: true,
                            timeOut: 3500,
                            positionClass: "toast-bottom-right",
                        });

                        let errors = xhr.responseJSON.errors || {};
                        $.each(errors, function(key, val) {
                            let input = document.getElementById(key.replace('buyer.', 'buyer-'));
                            input = $(input);
                            input.addClass('is-invalid');
                            input.parent().find('.invalid-feedback').remove();
                            input.parent().append(
                                '<span class="invalid-feedback" role="alert"><strong>' +
                                val[0] + '</strong></span>'
                            );
                        });
                        spinner.addClass('d-none');
                        btnText.text('Simpan');
                        submitBtn.prop('disabled', false);
                    }
                }
            });
        });

        $(document).on('click', '.delete-button', function(e) {
            e.preventDefault();

            const form = $(this).closest('form');

            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data ini akan dihapus secara permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<span class="swal-btn-text">Ya, Hapus</span>',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: false,
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-danger mx-2',
                    cancelButton: 'btn btn-secondary'
                },
                preConfirm: () => {
                    return new Promise((resolve) => {
                        const confirmBtn = Swal.getConfirmButton();
                        const btnText = confirmBtn.querySelector('.swal-btn-text');

                        btnText.innerHTML =
                            `<span class="spinner-border spinner-border-sm mx-2" role="status" aria-hidden="true"></span> Menghapus...`;
                        confirmBtn.disabled = true;

                        $.ajax({
                            url: form.attr('action'),
                            method: 'POST',
                            data: form.serialize(),
                            success: function() {
                                audio.play();
                                toastr.success("Data telah dihapus!", "BERHASIL", {
                                    progressBar: true,
                                    timeOut: 3500,
                                    positionClass: "toast-bottom-right"
                                });

                                $('.data-table').DataTable().ajax.reload(null,
                                    false);
                                Swal.close();
                            },
                            error: function() {
                                audio.play();
                                toastr.error("Gagal menghapus data.", "GAGAL!", {
                                    progressBar: true,
                                    timeOut: 3500,
                                    positionClass: "toast-bottom-right"
                                });

                                btnText.innerHTML = `Ya, Hapus`;
                                confirmBtn.disabled = false;
                            }
                        });
                    });
                }
            });
        });
    </script>
@endpush
