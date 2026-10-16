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
                                    <h3 class="font-weight-bold text-lg">Data SPR</h3>
                                    <div class="d-flex align-items-center">
                                        @if ($permissions['tambah'])
                                            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal"
                                                data-target="#modalForm">
                                                <i class="fas fa-plus"></i> Tambah SPR
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
                                            <th>Nama</th>
                                            <th>Lokasi Unit</th>
                                            <th>Alamat</th>
                                            <th>No. Telp</th>
                                            <th>Nama Marketing</th>
                                            <th width="150px">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="modal fade" id="modalForm" tabindex="-1" role="dialog" data-focus="false"
            aria-labelledby="modalFormLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-indigo">
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
                                <label for="id_customer" class="col-sm-3 col-form-label">Customer</label>
                                <div class="col-sm-8">
                                    <select name="id_customer" id="id_customer" class="form-control select-customer">
                                        <option value=""></option>
                                        @foreach ($customerList as $c)
                                            <option value="{{ $c->id }}">{{ $c->nama_lengkap }} ({{ $c->nik }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <hr>
                            <div class="form-group row">
                                <label for="no_sppr" class="col-sm-3 col-form-label">No. SPR</label>
                                <div class="col-sm-8"><input type="text" name="no_sppr" id="no_sppr" class="form-control" maxlength="100" required></div>
                            </div>
                            <div class="form-group row">
                                <label for="nama" class="col-sm-3 col-form-label">Nama Pembeli</label>
                                <div class="col-sm-8"><input type="text" name="nama" id="nama" class="form-control" readonly></div>
                            </div>
                            <div class="form-group row">
                                <label for="alamat" class="col-sm-3 col-form-label">Alamat</label>
                                <div class="col-sm-8"><textarea name="alamat" id="alamat" class="form-control" rows="3" readonly></textarea></div>
                            </div>
                            <div class="form-group row">
                                <label for="nik" class="col-sm-3 col-form-label">No. KTP</label>
                                <div class="col-sm-8"><input type="text" name="nik" id="nik" class="form-control" readonly></div>
                            </div>
                            <div class="form-group row">
                                <label for="no_telp" class="col-sm-3 col-form-label">No. Telp/HP</label>
                                <div class="col-sm-8"><input type="text" name="no_telp" id="no_telp" class="form-control" readonly></div>
                            </div>
                            <div class="form-group row">
                                <label for="lokasi_unit" class="col-sm-3 col-form-label">Lokasi Unit</label>
                                <div class="col-sm-8"><input type="text" name="lokasi_unit" id="lokasi_unit" class="form-control" readonly></div>
                            </div>
                            <div class="form-group row">
                                <label for="luas_bangunan" class="col-sm-3 col-form-label">Luas Bangunan (m&sup2;)</label>
                                <div class="col-sm-8"><input type="text" name="luas_bangunan" id="luas_bangunan" class="form-control" readonly></div>
                            </div>
                            <div class="form-group row">
                                <label for="luas_tanah" class="col-sm-3 col-form-label">Luas Tanah (m&sup2;)</label>
                                <div class="col-sm-8"><input type="text" name="luas_tanah" id="luas_tanah" class="form-control" readonly></div>
                            </div>
                            <div class="form-group row">
                                <label for="harga_jual" class="col-sm-3 col-form-label">Harga Jual</label>
                                <div class="col-sm-8"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">Rp</span></div><input type="text" name="harga_jual" id="harga_jual" class="form-control rupiah"></div></div>
                            </div>
                            <div class="form-group row">
                                <label for="nominal_dp" class="col-sm-3 col-form-label">Uang Muka</label>
                                <div class="col-sm-8"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">Rp</span></div><input type="text" name="nominal_dp" id="nominal_dp" class="form-control rupiah"></div></div>
                            </div>
                            <div class="form-group row">
                                <label for="asumsi_plafon_kpr" class="col-sm-3 col-form-label">Pokok Kredit Bank</label>
                                <div class="col-sm-8"><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">Rp</span></div><input type="text" name="asumsi_plafon_kpr" id="asumsi_plafon_kpr" class="form-control rupiah"></div></div>
                            </div>
                            <div class="form-group row">
                                <label for="penandatangan" class="col-sm-3 col-form-label">Nama Penjual / Penandatangan</label>
                                <div class="col-sm-8"><input type="text" name="penandatangan" id="penandatangan" class="form-control"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
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
        $(document).on('click', '[data-target="#modalForm"]', function() {
            $('#modalFormLabel').text('Tambah SPR');
            $('#id_customer').val('').trigger('change').prop('disabled', false);
            $('#no_sppr').val('');
            $('#penandatangan').val('RIKI KRIESNA,SE');
        });

        var audio = new Audio('{{ asset('audio/notification.ogg') }}');
        var permissions = @json($permissions);
        var showActionColumn = (permissions['edit'] == 1 || permissions['hapus'] == 1);

        $(function() {
            $('.select-customer').select2({
                theme: "bootstrap4",
                width: '100%',
                placeholder: "Pilih Customer",
                // dropdownParent: $('#modalForm'),
            });

            var table = $('.data-table').DataTable({
                processing: false,
                serverSide: false,
                ordering: false,
                responsive: true,
                ajax: "{{ route('sppr.index') }}",
                columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                }, {
                    data: 'customer_nama',
                    name: 'customer_nama',
                    orderable: false,
                    searchable: true
                }, {
                    data: 'lokasi_unit',
                    name: 'lokasi_unit',
                    orderable: false,
                    searchable: true
                }, {
                    data: 'alamat',
                    name: 'alamat',
                    orderable: false,
                    searchable: true
                }, {
                    data: 'no_telp',
                    name: 'no_telp',
                    orderable: false,
                    searchable: true
                }, {
                    data: 'nama_marketing',
                    name: 'nama_marketing',
                    orderable: false,
                    searchable: true
                }, {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    visible: showActionColumn,
                    className: 'text-center'
                }],
                columnDefs: [{
                    targets: 0,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                }]
            });
        });

        let customerRequest;
        $(document).on('change', '#id_customer', function() {
            if (customerRequest) customerRequest.abort();
            const id = $(this).val();
            $('#nama, #alamat, #nik, #no_telp, #lokasi_unit, #luas_bangunan, #luas_tanah, #harga_jual, #nominal_dp, #asumsi_plafon_kpr').val('');
            if (!id) return;
            customerRequest = $.get('{{ route('sppr.get-customer-detail', ':id') }}'.replace(':id', id), function(res) {
                if (String($('#id_customer').val()) !== String(id)) return;
                if (res.status === 'success') {
                    Object.entries(res.data).forEach(([key, value]) => {
                        $('#' + key).val(key === 'harga_jual' ? formatNumber(value) : value);
                    });
                }
            }).fail(function(xhr, status) {
                if (status !== 'abort') toastr.error('Data customer gagal dimuat. Silakan pilih kembali.');
            });
        });

        $(document).on('input', '.rupiah', function() {
            const value = $(this).val().replace(/\D/g, '');
            $(this).val(value ? formatRupiah(value) : '');
        });

        function formatRupiah(angka) {
            let number_string = angka.replace(/\D/g, ''),
                split = number_string.split(''),
                sisa = split.length % 3,
                rupiah = split.slice(0, sisa).join(''),
                ribuan = split.slice(sisa).join('').match(/\d{3}/g);

            if (ribuan) {
                let separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }
            return rupiah;
        }

        function formatNumber(num) {
            if (!num && num !== 0) return '';
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function unformatNumber(str) {
            if (!str) return 0;
            return parseInt(str.replace(/\./g, '')) || 0;
        }

        $(document).on('click', '.edit-button', function() {
            var url = $(this).data('url');
            $.get(url, function(response) {
                if (response.status === 'success') {
                    $('#modalFormLabel').text('Edit SPR');
                    let d = response.data;
                    $('#formData')[0].reset();
                    $('#primary_id').val(d.id);
                    $('#id_customer').val(d.id_customer).trigger('change.select2').prop('disabled', true);
                    const moneyFields = ['harga_jual', 'nominal_dp', 'asumsi_plafon_kpr'];
                    $('#formData [name]').each(function() {
                        const key = this.name;
                        if (key === 'id_customer' || key === 'primary_id' || key === '_token') return;
                        $(this).val(moneyFields.includes(key) ? formatNumber(d[key] ?? 0) : (d[key] ?? ''));
                    });
                    $('#penandatangan').val(d.penandatangan || 'RIKI KRIESNA,SE');
                    $('#modalForm').modal('show');
                }
            });
        });

        $('#modalForm').on('hidden.bs.modal', function() {
            if (customerRequest) customerRequest.abort();
            $('#formData')[0].reset();
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            $('#primary_id').val('');
            $('#id_customer').val('').trigger('change').prop('disabled', false);
            $('#no_sppr').val('');
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
            let url = id ? '{{ route('sppr.update', ['sppr' => ':id']) }}'.replace(':id', id) :
                '{{ route('sppr.store') }}';
            let method = id ? 'PUT' : 'POST';

            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            let formData = new FormData(this);

            if (id) {
                formData.set('id_customer', $('#id_customer').val());
            }

            let rupiahFields = ['harga_jual', 'nominal_dp', 'asumsi_plafon_kpr'];
            rupiahFields.forEach(function(field) {
                let val = $('#' + field).val();
                formData.set(field, unformatNumber(val));
            });

            formData.append('_method', method);

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    $('#modalForm').modal('hide');
                    audio.play();
                    let msg = id ? "Data berhasil diupdate!" : "Data berhasil ditambahkan!";
                    toastr.success(msg, "BERHASIL", {
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
                    if (xhr.status !== 422) toastr.error('Data gagal disimpan. Silakan coba kembali.');
                    if (xhr.status === 422) {
                        audio.play();
                        toastr.error("Ada inputan yang salah!", "GAGAL!", {
                            progressBar: true,
                            timeOut: 3500,
                            positionClass: "toast-bottom-right",
                        });

                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, val) {
                            let input = $('#' + key);
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
                            '<span class="spinner-border spinner-border-sm mx-2" role="status" aria-hidden="true"></span> Menghapus...';
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
                                $('.data-table').DataTable().ajax.reload(null, false);
                                Swal.close();
                            },
                            error: function() {
                                audio.play();
                                toastr.error("Gagal menghapus data.", "GAGAL!", {
                                    progressBar: true,
                                    timeOut: 3500,
                                    positionClass: "toast-bottom-right"
                                });
                                btnText.innerHTML = 'Ya, Hapus';
                                confirmBtn.disabled = false;
                            }
                        });
                    });
                }
            });
        });
    </script>
@endpush
