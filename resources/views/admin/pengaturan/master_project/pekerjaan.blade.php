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
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="font-weight-bold text-lg mb-0">Jenis Pekerjaan Master Project</h3>
                                        <small class="text-muted">{{ $project->jenis_proyek }}</small>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <button type="button" class="btn btn-sm btn-primary mr-2" data-toggle="modal"
                                            data-target="#modalForm">
                                            <i class="fas fa-plus"></i> Tambah Jenis Pekerjaan
                                        </button>
                                        <a href="{{ route('master-project.index') }}" class="btn btn-sm btn-secondary">Kembali</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-striped data-table">
                                    <thead>
                                        <tr>
                                            <th width="50px">No</th>
                                            <th>Nama Jenis Pekerjaan</th>
                                            <th width="180px">Jumlah Material</th>
                                            <th width="220px">Jumlah Ongkos Tukang / Jasa</th>
                                            <th width="320px">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($pekerjaanList as $item)
                                            <tr>
                                                <td></td>
                                                <td>{{ $item->nama_pekerjaan }}</td>
                                                <td>Rp {{ number_format((int) $item->details->where('tipe', 'material')->sum('sub_total'), 0, ',', '.') }}</td>
                                                <td>Rp {{ number_format((int) $item->details->where('tipe', 'jasa')->sum('sub_total'), 0, ',', '.') }}</td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center">
                                                        <button type="button" class="btn btn-info btn-sm mx-1 edit-button"
                                                            data-url="{{ route('master-project.pekerjaan.edit', $item->id) }}">Edit</button>
                                                        <a href="{{ route('master-project.material', $item->id) }}" class="btn btn-primary btn-sm mx-1">Data Material</a>
                                                        <a href="{{ route('master-project.jasa', $item->id) }}" class="btn btn-warning btn-sm mx-1">Data Jasa</a>
                                                        <form action="{{ route('master-project.pekerjaan.destroy', $item->id) }}" method="POST" style="display:inline;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="delete-button btn btn-danger btn-sm mx-1">Hapus</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
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
                                <label for="nama_pekerjaan" class="col-sm-3 col-form-label">Nama Jenis Pekerjaan</label>
                                <div class="col-sm-9">
                                    <input type="text" name="nama_pekerjaan" id="nama_pekerjaan" class="form-control">
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
        $(function() {
            const successMessage = sessionStorage.getItem('success');
            if (successMessage) {
                audio.play();
                toastr.success(successMessage, 'BERHASIL', {
                    progressBar: true,
                    timeOut: 3500,
                    positionClass: 'toast-bottom-right',
                });
                sessionStorage.removeItem('success');
            }
        });

        $(function() {
            $('.data-table').DataTable({
                ordering: false,
                responsive: true,
                columnDefs: [{
                    targets: 0,
                    render: function(data, type, row, meta) {
                        return meta.row + 1;
                    }
                }]
            });
        });

        $(document).on('click', '[data-target="#modalForm"]', function() {
            $('#modalFormLabel').text('Tambah Jenis Pekerjaan');
            $('#primary_id').val('');
            $('#nama_pekerjaan').val('');
        });

        $(document).on('click', '.edit-button', function() {
            $.get($(this).data('url'), function(response) {
                if (response.status === 'success') {
                    $('#modalFormLabel').text('Edit Jenis Pekerjaan');
                    $('#primary_id').val(response.data.id);
                    $('#nama_pekerjaan').val(response.data.nama_pekerjaan);
                    $('#modalForm').modal('show');
                }
            });
        });

        $('#modalForm').on('hidden.bs.modal', function() {
            $('#formData')[0].reset();
            $('#primary_id').val('');
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            const submitBtn = $('#submitBtn');
            submitBtn.find('.spinner-border').addClass('d-none');
            submitBtn.find('.button-text').text('Simpan');
            submitBtn.prop('disabled', false);
        });

        $('#formData').on('submit', function(e) {
            e.preventDefault();

            const submitBtn = $('#submitBtn');
            const spinner = submitBtn.find('.spinner-border');
            const btnText = submitBtn.find('.button-text');

            spinner.removeClass('d-none');
            btnText.text('Menyimpan...');
            submitBtn.prop('disabled', true);

            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            const id = $('#primary_id').val();
            const url = id ? '{{ route('master-project.pekerjaan.update', ['pekerjaan' => ':id']) }}'.replace(':id', id) :
                '{{ route('master-project.pekerjaan.store', $project->id) }}';
            const method = id ? 'PUT' : 'POST';
            const formData = new FormData(this);
            formData.append('_method', method);

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function() {
                    sessionStorage.setItem('success', id ? 'Jenis pekerjaan berhasil diupdate!' : 'Jenis pekerjaan berhasil ditambahkan!');
                    window.location.reload();
                },
                error: function(xhr) {
                    audio.play();

                    if (xhr.status === 422) {
                        toastr.error('Ada inputan yang salah!', 'GAGAL!', {
                            progressBar: true,
                            timeOut: 3500,
                            positionClass: 'toast-bottom-right',
                        });

                        $.each(xhr.responseJSON.errors || {}, function(key, val) {
                            let input = $('#' + key);
                            input.addClass('is-invalid');
                            input.parent().find('.invalid-feedback').remove();
                            input.parent().append('<span class="invalid-feedback" role="alert"><strong>' + val[0] + '</strong></span>');
                        });
                    } else {
                        toastr.error(xhr.responseJSON?.message || 'Gagal menyimpan jenis pekerjaan.', 'GAGAL!', {
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
                text: 'Jenis pekerjaan ini akan dihapus secara permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<span class="swal-btn-text">Ya, Hapus</span>',
                cancelButtonText: 'Batal',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-danger mx-2',
                    cancelButton: 'btn btn-secondary'
                },
                preConfirm: () => {
                    return new Promise((resolve) => {
                        const confirmBtn = Swal.getConfirmButton();
                        const btnText = confirmBtn.querySelector('.swal-btn-text');

                        btnText.innerHTML = `<span class="spinner-border spinner-border-sm mx-2" role="status" aria-hidden="true"></span> Menghapus...`;
                        confirmBtn.disabled = true;

                        $.ajax({
                            url: form.attr('action'),
                            method: 'POST',
                            data: form.serialize(),
                            success: function() {
                                sessionStorage.setItem('success', 'Jenis pekerjaan berhasil dihapus!');
                                window.location.reload();
                            },
                            error: function() {
                                audio.play();
                                toastr.error('Gagal menghapus jenis pekerjaan.', 'GAGAL!', {
                                    progressBar: true,
                                    timeOut: 3500,
                                    positionClass: 'toast-bottom-right'
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
