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
                                    <h3 class="font-weight-bold text-lg">Data Kelengkapan Berkas</h3>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered small table-striped data-table">
                                    <thead>
                                        <tr>
                                            <th width="4%">No</th>
                                            <th width="25%">Nama Customer</th>
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                            <div><strong>Berkas pengajuan</strong><div class="small text-muted">Upload atau pilih berkas customer. Perubahan diterapkan saat Simpan.</div></div>
                            <a id="customerFilesLink" class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener">File Customer</a>
                        </div>
                        <div class="row">
                        @foreach ($jenisBerkas as $jenis)
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 h-100 berkas-card" data-jenis="{{ $jenis->id }}">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="mb-0 font-weight-bold" for="status_berkas_{{ $jenis->id }}">{{ $jenis->nama }}</label>
                                        <select name="status_berkas[{{ $jenis->id }}]" id="status_berkas_{{ $jenis->id }}" class="form-control form-control-sm select-status-berkas" style="width:120px">
                                            <option value="0">Belum Ada</option><option value="1">Ada</option>
                                        </select>
                                    </div>
                                    <div class="berkas-preview bg-light rounded d-flex align-items-center justify-content-center mb-2" id="preview_berkas_{{ $jenis->id }}" style="height:190px;overflow:hidden"><span class="text-muted small">Belum ada berkas</span></div>
                                    <div class="small text-truncate mb-2" id="nama_berkas_{{ $jenis->id }}"></div>
                                    <div class="d-flex align-items-center mb-2">
                                        <label class="btn btn-outline-primary btn-sm mb-0 mr-2" for="file_berkas_{{ $jenis->id }}"><i class="fas fa-upload mr-1"></i>Upload / Ganti</label>
                                        <input type="file" class="sr-only upload-berkas" id="file_berkas_{{ $jenis->id }}" name="file_berkas[{{ $jenis->id }}]" data-jenis="{{ $jenis->id }}" accept=".pdf,.jpg,.jpeg,.png">
                                        <a id="lihat_berkas_{{ $jenis->id }}" class="btn btn-outline-secondary btn-sm mr-2 d-none" target="_blank" rel="noopener">Buka</a>
                                        <button type="button" class="btn btn-outline-danger btn-sm hapus-berkas d-none" data-jenis="{{ $jenis->id }}"><i class="fas fa-trash mr-1"></i>Hapus</button>
                                    </div>
                                    <select name="pilih_berkas[{{ $jenis->id }}]" id="pilih_berkas_{{ $jenis->id }}" class="form-control form-control-sm pilih-berkas" data-jenis="{{ $jenis->id }}"><option value="">Pilih dari berkas customer?</option></select>
                                    <input type="checkbox" class="d-none hapus-flag" id="hapus_berkas_{{ $jenis->id }}" name="hapus_berkas[]" value="{{ $jenis->id }}">
                                    <small class="text-muted">PDF, JPG, PNG ? Maks. 10 MB</small>
                                </div>
                            </div>
                        @endforeach
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Percakapan WA</label>
                            <div class="col-sm-5">
                                <input type="file" class="mb-2" id="percakapan_wa" name="percakapan_wa"
                                    accept=".jpg, .jpeg, .png">
                                <div class="img-thumbnail mb-2 d-flex align-items-center justify-content-center"
                                    id="previewPercakapanWa"
                                    style="max-width: 150px; height: 150px; background-color: #f8f9fa; border: 1px solid #dee2e6; overflow: hidden;">
                                    <span style="color: #6c757d;">Tidak ada foto</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Catatan Berkas</label>
                            <div class="col-md-8">
                                <textarea name="catatan_kekurangan" class="form-control" id="catatan_kekurangan" rows="3"></textarea>
                            </div>
                        </div>

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
    <div class="modal fade" id="printModal" tabindex="-1" role="dialog" aria-labelledby="printTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm"><form id="printForm" method="GET" target="_blank" class="modal-content">
            <div class="modal-header"><h5 id="printTitle" class="modal-title">Cetak berkas</h5><button type="button" class="close" data-dismiss="modal" aria-label="Tutup">&times;</button></div>
            <div class="modal-body">
                <div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" name="ukuran_asli" value="1" id="ukuranAsli"><label class="custom-control-label" for="ukuranAsli">Ukuran asli di tengah halaman</label></div>
                <small class="text-muted d-block mt-2">Tanpa centang: isi memenuhi halaman A4 dengan proporsi tetap. Gambar memakai ukuran 96 DPI; berkas lebih besar dari A4 diperkecil agar muat.</small>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Cetak PDF</button></div>
        </form></div>
    </div>
@endsection
@push('scripts')
    <script>
        previewFile('percakapan_wa', 'previewPercakapanWa');

        $(document).ready(function() {
            $('.select-status-berkas').select2({
                theme: "bootstrap4",
                placeholder: "Pilih Status",
                minimumResultsForSearch: Infinity
            });
        });

        $(function() {
            var permissions = @json($permissions);
            var showActionColumn = true;

            var table = $('.data-table').DataTable({
                processing: false,
                serverSide: false,
                ordering: false,
                responsive: true,
                ajax: "{{ route('pengajuan-berkas.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'nama_customer',
                        name: 'nama_customer',
                        orderable: false,
                        searchable: true
                    },
                    @foreach ($jenisBerkas as $jenis)
                    {
                        data: 'berkas_{{ $jenis->id }}',
                        name: 'berkas_{{ $jenis->id }}',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    @endforeach
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        visible: showActionColumn
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

        let berkasUrls = {};
        function previewBerkas(id, url, name) {
            const box = $('#preview_berkas_' + id).empty();
            if (!url) box.append($('<span class="text-muted small">').text('Belum ada berkas'));
            else if (/\.pdf$/i.test(name)) box.append($('<iframe title="Preview PDF" class="border-0 w-100 h-100">').attr('src', url));
            else box.append($('<img class="w-100 h-100" alt="Preview berkas" style="object-fit:contain">').attr('src', url));
            $('#nama_berkas_' + id).text(name || '');
            $('#lihat_berkas_' + id).toggleClass('d-none', !url).attr('href', url || '');
            $('.hapus-berkas[data-jenis="' + id + '"]').toggleClass('d-none', !url);
        }
        $(document).on('change', '.upload-berkas', function() {
            const id = $(this).data('jenis');
            if (!this.files.length) return;
            const file = this.files[0];
            if (berkasUrls[id]) URL.revokeObjectURL(berkasUrls[id]);
            berkasUrls[id] = URL.createObjectURL(file);
            $('#pilih_berkas_' + id).val('');
            $('#hapus_berkas_' + id).prop('checked', false);
            $('#status_berkas_' + id).val('1').trigger('change');
            previewBerkas(id, berkasUrls[id], file.name);
        });
        $(document).on('change', '.pilih-berkas', function() {
            const id = $(this).data('jenis'), option = $(this).find(':selected');
            if (!this.value) return;
            $('#file_berkas_' + id).val('');
            $('#hapus_berkas_' + id).prop('checked', false);
            $('#status_berkas_' + id).val('1').trigger('change');
            previewBerkas(id, option.data('url'), option.data('filename'));
        });
        $(document).on('click', '.hapus-berkas', function() {
            const id = $(this).data('jenis');
            $('#file_berkas_' + id).val('');
            $('#pilih_berkas_' + id).val('');
            $('#hapus_berkas_' + id).prop('checked', true);
            $('#status_berkas_' + id).val('0').trigger('change');
            previewBerkas(id, null, 'Akan dihapus saat Simpan');
        });
        $(document).on('click', '.print-berkas', function(e) {
            e.preventDefault();
            $('#printForm').attr('action', this.href);
            $('#printModal').modal('show');
        });
        $('#printForm').on('submit', function() { $('#printModal').modal('hide'); });

        // Tombol edit
        $(document).on('click', '.edit-button', function() {
            var url = $(this).data('url');

            $.get(url, function(response) {
                if (response.status === 'success') {
                    $('#modalFormLabel').text('Edit Pengajuan Berkas');

                    $('#primary_id').val(response.data.id);
                    $('#nama_lengkap').val(response.data.customer.nama_lengkap);
                    const statusBerkas = response.data.status_jenis_berkas || {};
                    const files = response.data.file_jenis_berkas || {};
                    $('#formData')[0].reset();
                    $('#primary_id').val(response.data.id);
                    $('#nama_lengkap').val(response.data.customer.nama_lengkap);
                    $('#customerFilesLink').attr('href', '{{ route('upload-file.index') }}?id_customer=' + response.data.id_customer);
                    @foreach ($jenisBerkas as $jenis)
                        const file{{ $jenis->id }} = files['{{ $jenis->id }}'];
                        $('#status_berkas_{{ $jenis->id }}').val(file{{ $jenis->id }} ? 1 : (statusBerkas['{{ $jenis->id }}'] ?? 0)).trigger('change');
                        previewBerkas('{{ $jenis->id }}', file{{ $jenis->id }} ? '{{ route('pengajuan-berkas.file', ['id' => ':id', 'jenis' => $jenis->id]) }}'.replace(':id', response.data.id) : null, file{{ $jenis->id }}?.name);
                        const select{{ $jenis->id }} = $('#pilih_berkas_{{ $jenis->id }}').empty().append($('<option value="">').text('Pilih dari berkas customer?'));
                        (response.customer_files || []).forEach(file => {
                            select{{ $jenis->id }}.append($('<option>').val(file.id).text(file.nama_file).attr('data-url', '{{ asset('assets/customer') }}/' + file.lampiran).attr('data-filename', file.lampiran));
                        });
                    @endforeach
                    $('#catatan_kekurangan').val(response.data.catatan_kekurangan);
                    setPreview(response.data.percakapan_wa, 'assets/legal/pengajuan_berkas/percakapan_wa',
                        'previewPercakapanWa');

                    $('#modalForm').modal('show');
                }
            });
        });

        $('#modalForm').on('hidden.bs.modal', function() {
            Object.values(berkasUrls).forEach(url => URL.revokeObjectURL(url));
            berkasUrls = {};
            $('.berkas-preview').empty();
            $('#formData')[0].reset();
            $('#primary_id').val('');
            $('[id^=lihat_berkas_]').addClass('d-none').removeAttr('href');
            $('.form-select').val('').trigger('change');
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            let submitBtn = $('#submitBtn');
            let spinner = submitBtn.find('.spinner-border');
            let btnText = submitBtn.find('.button-text');

            spinner.addClass('d-none');
            btnText.text('Simpan');
            submitBtn.prop('disabled', false);

            $('#previewPercakapanWa').html('<span style="color: #6c757d;">Tidak ada foto</span>');
        });

        // Simpan / Update data
        $('#formData').on('submit', function(e) {
            e.preventDefault();

            let submitBtn = $('#submitBtn');
            let spinner = submitBtn.find('.spinner-border');
            let btnText = submitBtn.find('.button-text');

            spinner.removeClass('d-none');
            btnText.text('Menyimpan...');
            submitBtn.prop('disabled', true);

            let id = $('#primary_id').val();
            let url = id ? '{{ route('pengajuan-berkas.update', ['pengajuan_berka' => ':id']) }}'.replace(':id',
                    id) :
                '{{ route('pengajuan-berkas.store') }}';
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
                    toastr.success("Berkas Pengajuan telah disimpan!", "BERHASIL", {
                        progressBar: true,
                        timeOut: 3500,
                        positionClass: "toast-bottom-right",
                    });
                    $('.data-table').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        audio.play();
                        toastr.error("Ada inputan yang salah!", "GAGAL!", {
                            progressBar: true,
                            timeOut: 3500,
                            positionClass: "toast-bottom-right",
                        });

                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, val) {
                            let input = key.startsWith('pilih_berkas.') ? $('#pilih_berkas_' + key.split('.')[1]) : key.startsWith('file_berkas.') ? $('#file_berkas_' + key.split('.')[1]) :
                                (key.startsWith('status_berkas.') ? $('#status_berkas_' + key.split('.')[1]) : $('#' + key));
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
                    } else {
                        toastr.error(xhr.responseJSON?.error || 'Gagal menyimpan berkas.');
                    }
                    spinner.addClass('d-none');
                    btnText.text('Simpan');
                    submitBtn.prop('disabled', false);
                }
            });
        });


        // Hapus data
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
