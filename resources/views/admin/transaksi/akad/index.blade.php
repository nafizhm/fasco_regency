@extends('admin.layout_admin')
@section('content')
    <style>
        #modalForm .modal-dialog { max-width: 860px; }
        #modalForm .modal-content { border: 0; border-radius: 12px; overflow: hidden; }
        #modalForm .modal-header { padding: 20px 24px; }
        #modalForm .modal-body { padding: 24px; background: #f4f6f9; max-height: calc(100vh - 210px); overflow-y: auto; }
        #modalForm .akad-form-section { min-width: 0; padding: 20px; margin-bottom: 20px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; }
        #modalForm .akad-form-section:last-child { margin-bottom: 0; }
        #modalForm .akad-section-title { font-size: 15px; font-weight: 700; color: #334155; margin-bottom: 4px; }
        #modalForm .akad-section-help { font-size: 13px; color: #64748b; margin-bottom: 18px; }
        #modalForm label { font-size: 13px; margin-bottom: 6px; }
        #modalForm .form-control { border-radius: 6px; }
        #modalForm .modal-footer { padding: 16px 24px; background: #fff; }
        #modalForm .modal-footer .btn { min-width: 100px; }
        @media (max-width: 575.98px) {
            #modalForm .modal-dialog { margin: 10px; }
            #modalForm .modal-body { padding: 14px; }
            #modalForm .akad-form-section { padding: 16px; }
        }
    </style>
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
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-indigo">
                    <div>
                        <h5 class="modal-title text-white font-weight-bold" id="modalFormLabel">Tambah Akad</h5>
                        <div class="small text-white mt-1">Kelola akad dan informasi pembeli dalam satu form.</div>
                    </div>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formData">
                    @csrf
                    <input type="hidden" id="primary_id" name="primary_id">
                    <div class="modal-body">
                        <section class="akad-form-section" aria-labelledby="akad-detail-heading">
                            <h6 class="akad-section-title" id="akad-detail-heading">Detail Akad</h6>
                            <p class="akad-section-help">Catat akad yang sudah dilaksanakan untuk satu pembeli.</p>
                            <div class="form-group">
                                <label for="id_customer">Pembeli <span class="text-danger" aria-hidden="true">*</span></label>
                                <select id="id_customer" name="id_customer" class="form-control" required>
                                    <option value="">Pilih pembeli</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->nama_lengkap }} (#{{ $customer->id }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="jenis_akad">Jenis Akad <span class="text-danger" aria-hidden="true">*</span></label>
                                    <select id="jenis_akad" name="jenis_akad" class="form-control" required>
                                        <option value="">Pilih jenis akad</option>
                                        <option value="AJB">AJB</option>
                                        <option value="PPJB">PPJB</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="tgl_akad">Tanggal Akad</label>
                                    <input type="date" id="tgl_akad" class="form-control" name="tgl_akad"
                                        required max="{{ now('Asia/Jakarta')->toDateString() }}" value="{{ now('Asia/Jakarta')->toDateString() }}">
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <label for="keterangan">Keterangan <span class="text-muted font-weight-normal">(opsional)</span></label>
                                <textarea id="keterangan" class="form-control" name="keterangan" rows="2" maxlength="255" placeholder="Tambahkan catatan terkait akad jika diperlukan"></textarea>
                            </div>
                        </section>

                        <fieldset id="buyer-fields" disabled style="display: none;" aria-labelledby="buyer-heading">
                            <div class="akad-form-section">
                                <h6 class="akad-section-title" id="buyer-heading">Data Pembeli</h6>
                                <p class="akad-section-help">Perubahan di bagian ini juga memperbarui data customer.</p>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="buyer-nama_lengkap">Nama Pembeli <span class="text-danger" aria-hidden="true">*</span></label>
                                        <input type="text" class="form-control" id="buyer-nama_lengkap" name="buyer[nama_lengkap]" maxlength="100" required autocomplete="name" style="text-transform: uppercase;">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="buyer-nik">NIK</label>
                                        <input type="text" class="form-control" id="buyer-nik" name="buyer[nik]" maxlength="50" inputmode="numeric">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="buyer-no_telp">Nomor Telepon</label>
                                        <input type="tel" class="form-control" id="buyer-no_telp" name="buyer[no_telp]" maxlength="20" autocomplete="tel">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="buyer-tgl_booking">Tanggal Booking</label>
                                        <input type="date" class="form-control" id="buyer-tgl_booking" name="buyer[tgl_booking]" max="{{ now('Asia/Jakarta')->toDateString() }}">
                                    </div>
                                    <div class="form-group col-12 mb-0">
                                        <label for="buyer-alamat_ktp">Alamat</label>
                                        <textarea class="form-control" id="buyer-alamat_ktp" name="buyer[alamat_ktp]" rows="2" autocomplete="street-address"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="akad-form-section">
                                <h6 class="akad-section-title" id="bank-marketing-heading">Bank &amp; Marketing</h6>
                                <p class="akad-section-help">Pilih bank KPR dan marketing yang menangani pembeli.</p>
                                <div class="row">
                                    <div class="form-group col-md-6 mb-md-0">
                                        <label for="buyer-id_bank_kpr">Bank KPR</label>
                                        <select class="form-control" id="buyer-id_bank_kpr" name="buyer[id_bank_kpr]">
                                            <option value="">Belum diisi</option>
                                            @foreach ($banks as $bank)<option value="{{ $bank->id }}">{{ $bank->nama }}</option>@endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6 mb-0">
                                        <label for="buyer-id_marketing">Marketing</label>
                                        <select class="form-control" id="buyer-id_marketing" name="buyer[id_marketing]">
                                            <option value="">Belum diisi</option>
                                            @foreach ($marketings as $marketing)<option value="{{ $marketing->id }}">{{ $marketing->nama_marketing }}</option>@endforeach
                                        </select>
                                    </div>
                                </div>
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
                    $('#modalFormLabel').text('Edit Akad');
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
            $('#modalFormLabel').text('Tambah Akad');
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
