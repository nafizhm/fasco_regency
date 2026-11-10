@extends('admin.layout_admin')
@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid"></div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header p-3">
                                <div class="d-flex align-content-center justify-content-between">
                                    <h3 class="font-weight-bold text-xl">Setting Proyek</h3>
                                    <div class="d-flex align-items-center">
                                        @if (isset($permissions['tambah']) && $permissions['tambah'] == 1)
                                            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal"
                                                data-target="#modalForm">
                                                <i class="fas fa-plus"></i> Tambah Setting
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-striped data-table">
                                    <thead>
                                        <tr>
                                            <th width="50px">No</th>
                                            <th>Lokasi Kavling</th>
                                            <th>Lokasi Unit</th>
                                            <th>Type Rumah</th>
                                            <th>Status</th>
                                            <th width="120px">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="modal fade" id="modalForm" tabindex="-1" role="dialog" data-focus="false"
            aria-labelledby="modalFormLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title text-white font-weight-bold" id="modalFormLabel"></h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="formData">
                        @csrf
                        <input type="hidden" id="primary_id" name="primary_id">
                        <div class="modal-body">
                            <div class="form-group row">
                                <label for="id_lokasi" class="col-sm-3 col-form-label">Lokasi Kavling</label>
                                <div class="col-sm-9">
                                    <select name="id_lokasi" id="id_lokasi" class="form-control select-lokasi">
                                        <option value=""></option>
                                        @foreach ($lokasiList as $lokasi)
                                            <option value="{{ $lokasi->id }}">{{ $lokasi->nama_kavling }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="id_kavling" class="col-sm-3 col-form-label">Lokasi Unit</label>
                                <div class="col-sm-9">
                                    <select name="id_kavling" id="id_kavling" class="form-control select-unit">
                                        <option value=""></option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="id_master_project" class="col-sm-3 col-form-label">Type Rumah</label>
                                <div class="col-sm-9">
                                    <select name="id_master_project" id="id_master_project"
                                        class="form-control select-type-rumah">
                                        <option value=""></option>
                                        @foreach ($masterProjectList as $project)
                                            <option value="{{ $project->id }}">{{ $project->jenis_proyek }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="status_proyek" class="col-sm-3 col-form-label">Status Proyek</label>
                                <div class="col-sm-9">
                                    <select name="status_proyek" id="status_proyek" class="form-control select-status">
                                        <option value=""></option>
                                        <option value="1">Sudah Disetting</option>
                                        <option value="2">Proses</option>
                                        <option value="3">Selesai</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary ms-1" id="submitBtn">
                                <span class="spinner-border spinner-border-sm mx-1 d-none" role="status"
                                    aria-hidden="true"></span>
                                <span class="button-text">Simpan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        function initSelect2() {
            $('.select-lokasi').select2({
                theme: 'bootstrap4',
                placeholder: 'Pilih Lokasi Kavling',
                dropdownParent: $('#modalForm')
            });

            $('.select-unit').select2({
                theme: 'bootstrap4',
                placeholder: 'Pilih Lokasi Unit',
                dropdownParent: $('#modalForm')
            });

            $('.select-type-rumah').select2({
                theme: 'bootstrap4',
                placeholder: 'Pilih Type Rumah',
                dropdownParent: $('#modalForm')
            });

            $('.select-status').select2({
                theme: 'bootstrap4',
                placeholder: 'Pilih Status Proyek',
                minimumResultsForSearch: Infinity,
                dropdownParent: $('#modalForm')
            });
        }

        function loadUnits(idLokasi, selectedId = '') {
            $('#id_kavling').empty().append('<option value=""></option>').trigger('change');

            if (!idLokasi) {
                return;
            }

            $.get('{{ route('setting-proyek.units-by-lokasi', ['id' => ':id']) }}'.replace(':id', idLokasi), function(
                data) {
                data.forEach(function(item) {
                    $('#id_kavling').append(
                        `<option value="${item.id_kavling}">${item.kode_kavling}</option>`);
                });

                if (selectedId) {
                    $('#id_kavling').val(String(selectedId)).trigger('change');
                } else {
                    $('#id_kavling').trigger('change');
                }
            });
        }

        $(document).on('click', '[data-target="#modalForm"]', function() {
            $('#modalFormLabel').text('Tambah Setting Proyek');
            $('#status_proyek').val('1').trigger('change');
        });

        var permissions = @json($permissions);
        var showActionColumn = (permissions['edit'] == 1 || permissions['hapus'] == 1);

        $(function() {
            initSelect2();

            $('.data-table').DataTable({
                processing: false,
                serverSide: true,
                ordering: false,
                responsive: true,
                ajax: "{{ route('setting-proyek.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'lokasi_kavling',
                        name: 'lokasi_kavling',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'lokasi_unit',
                        name: 'lokasi_unit',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'type_rumah',
                        name: 'type_rumah',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'status_label',
                        name: 'status_label',
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
        });

        $(document).on('change', '#id_lokasi', function() {
            loadUnits($(this).val());
        });

        $(document).on('click', '.edit-button', function() {
            var url = $(this).data('url');
            $.get(url, function(response) {
                if (response.status === 'success') {
                    $('#modalFormLabel').text('Edit Setting Proyek');
                    $('#primary_id').val(response.data.id);
                    $('#id_lokasi').val(String(response.data.id_lokasi)).trigger('change');
                    $('#id_master_project').val(String(response.data.id_master_project)).trigger('change');
                    $('#status_proyek').val(String(response.data.status)).trigger('change');
                    loadUnits(response.data.id_lokasi, response.data.id_kavling);
                    $('#modalForm').modal('show');
                }
            });
        });

        $('#modalForm').on('hidden.bs.modal', function() {
            $('#formData')[0].reset();
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            $('#primary_id').val('');
            $('#id_lokasi').val('').trigger('change');
            $('#id_kavling').empty().append('<option value=""></option>').trigger('change');
            $('#id_master_project').val('').trigger('change');
            $('#status_proyek').val('').trigger('change');

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
            let url = id ? '{{ route('setting-proyek.update', ['setting_proyek' => ':id']) }}'.replace(':id', id) :
                '{{ route('setting-proyek.store') }}';
            let method = id ? 'PUT' : 'POST';

            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            let formData = new FormData(this);
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
                    let msg = id ? 'Data berhasil diupdate!' : 'Data berhasil ditambahkan!';
                    toastr.success(msg, 'BERHASIL', {
                        progressBar: true,
                        timeOut: 3500,
                        positionClass: 'toast-bottom-right',
                    });
                    $('.data-table').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    audio.play();

                    if (xhr.status === 422) {
                        toastr.error('Ada inputan yang salah!', 'GAGAL!', {
                            progressBar: true,
                            timeOut: 3500,
                            positionClass: 'toast-bottom-right',
                        });

                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, val) {
                            let input = $('#' + key);
                            input.addClass('is-invalid');
                            input.parent().find('.invalid-feedback').remove();
                            input.parent().append(
                                '<span class="invalid-feedback d-block" role="alert"><strong>' +
                                val[0] + '</strong></span>'
                            );
                        });
                    } else {
                        toastr.error(xhr.responseJSON?.message || 'Gagal menyimpan data.', 'GAGAL!', {
                            progressBar: true,
                            timeOut: 3500,
                            positionClass: 'toast-bottom-right',
                        });
                    }

                    spinner.addClass('d-none');
                    btnText.text('Simpan');
                    submitBtn.prop('disabled', false);
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
                                toastr.success('Data telah dihapus!', 'BERHASIL', {
                                    progressBar: true,
                                    timeOut: 3500,
                                    positionClass: 'toast-bottom-right'
                                });

                                $('.data-table').DataTable().ajax.reload(null, false);
                                Swal.close();
                            },
                            error: function() {
                                audio.play();
                                toastr.error('Gagal menghapus data.', 'GAGAL!', {
                                    progressBar: true,
                                    timeOut: 3500,
                                    positionClass: 'toast-bottom-right'
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
