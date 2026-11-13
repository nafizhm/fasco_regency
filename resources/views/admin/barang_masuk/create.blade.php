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
                                <h3 class="font-weight-bold text-lg mb-0">Tambah Barang Masuk</h3>
                            </div>
                            <div class="card-body">
                                <form id="formData" enctype="multipart/form-data">
                                    @csrf
                                    <div class="row mb-3">
                                        <label for="tanggal" class="col-sm-2 col-form-label">Tanggal Masuk</label>
                                        <div class="col-sm-3">
                                            <input type="date" id="tanggal" name="tanggal" class="form-control"
                                                value="{{ date('Y-m-d') }}">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="jatuh_tempo" class="col-sm-2 col-form-label">Jatuh Tempo Pembayaran</label>
                                        <div class="col-sm-3">
                                            <input type="date" id="jatuh_tempo" name="jatuh_tempo" class="form-control"  aria-describedby="jatuh-tempo-help">
                                            <small id="jatuh-tempo-help" class="form-text text-muted">Opsional. Isi sesuai kesepakatan pembayaran dengan supplier.</small>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="id_supplier" class="col-sm-2 col-form-label">Supplier</label>
                                        <div class="col-sm-4">
                                            <select class="form-select select-supplier" name="id_supplier" id="id_supplier">
                                                <option value=""></option>
                                                @foreach ($supplierList as $supplier)
                                                    <option value="{{ $supplier->id }}">{{ $supplier->nama }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <label for="nama_penerima" class="col-sm-2 col-form-label">Nama Penerima</label>
                                        <div class="col-sm-4">
                                            <input type="text" id="nama_penerima" name="nama_penerima" class="form-control">
                                        </div>
                                    </div>

                                    <div class="row mb-3 d-none">
                                        <label for="jumlah_item_display" class="col-sm-2 col-form-label">Jumlah Item</label>
                                        <div class="col-sm-3">
                                            <input type="text" id="jumlah_item_display" class="form-control" value="0" readonly>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <label for="total_harga_display" class="col-sm-2 col-form-label">Total Harga</label>
                                        <div class="col-sm-3">
                                            <input type="text" id="total_harga_display" class="form-control" value="0" readonly>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="font-weight-bold mb-0">Daftar Barang</h5>
                                        <button type="button" class="btn btn-primary btn-sm" id="addRowBtn">
                                            <i class="fas fa-plus"></i> Tambah Barang
                                        </button>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered w-100" id="barangTable">
                                            <thead>
                                                <tr>
                                                    <th width="50px">No</th>
                                                    <th>Barang</th>
                                                    <th width="140px">Jumlah</th>
                                                    <th width="160px">Satuan</th>
                                                    <th width="180px">Harga Beli</th>
                                                    <th width="200px">Total per Item</th>
                                                    <th width="90px">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>

                                    <hr>

                                    @include('admin.barang_masuk.attachment_fields')

                                    <div class="modal-footer px-0 pb-0">
                                        <button type="submit" class="btn btn-primary ms-1" id="submitBtn">
                                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                                aria-hidden="true"></span>
                                            <span class="button-text">Simpan</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    @php
        $barangOptions = $barangList
            ->map(function ($barang) {
                return [
                    'id' => $barang->id,
                    'text' => $barang->nama,
                    'satuan' => $barang->satuan?->nama_satuan ?? '',
                ];
            })
            ->values();
    @endphp
    <script>
        const barangOptions = @json($barangOptions);

        function barangSelectOptions(selectedId = '') {
            const options = ['<option value=""></option>'];

            barangOptions.forEach((barang) => {
                const selected = String(selectedId) === String(barang.id) ? 'selected' : '';
                options.push(
                    `<option value="${barang.id}" data-satuan="${barang.satuan ?? ''}" ${selected}>${barang.text}</option>`
                );
            });

            return options.join('');
        }

        function renderRow(item = {}) {
            return `
                <tr>
                    <td class="row-number text-center align-middle"></td>
                    <td>
                        <select class="form-select select-barang" name="id_barang[]">
                            ${barangSelectOptions(item.id_barang ?? '')}
                        </select>
                    </td>
                    <td>
                        <input type="text" name="jumlah[]" class="form-control format-number" value="${item.jumlah ?? ''}">
                    </td>
                    <td>
                        <input type="text" class="form-control satuan-field" value="${item.satuan ?? ''}" readonly>
                    </td>
                    <td>
                        <input type="text" name="harga_beli[]" class="form-control format-number" value="${item.harga_beli ?? ''}">
                    </td>
                    <td>
                        <input type="text" class="form-control total-item-field text-right" value="Rp 0" aria-label="Total per item" readonly>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm remove-row">Hapus</button>
                    </td>
                </tr>
            `;
        }

        function refreshRowNumbers() {
            $('#barangTable tbody tr').each(function(index) {
                $(this).find('.row-number').text(index + 1);
            });
        }

        function initSelect2(scope) {
            scope.find('.select-barang').select2({
                theme: 'bootstrap4',
                placeholder: 'Pilih Barang',
                width: '100%'
            });
        }

        function addRow(item = {}) {
            const row = $(renderRow(item));
            $('#barangTable tbody').append(row);
            initSelect2(row);
            if (item.id_barang) {
                row.find('.select-barang').val(String(item.id_barang)).trigger('change');
            } else {
                updateSatuanField(row.find('.select-barang'));
            }
            refreshRowNumbers();
            refreshSummary();
        }

        function updateSatuanField(selectEl) {
            const satuan = selectEl.find('option:selected').data('satuan') || '';
            selectEl.closest('tr').find('.satuan-field').val(satuan);
        }

        function parseFormattedNumber(value) {
            return Number(String(value || '').replace(/[^\d]/g, '')) || 0;
        }

        function refreshSummary() {
            let totalHarga = 0;
            let jumlahItem = $('#barangTable tbody tr').length;

            $('#barangTable tbody tr').each(function() {
                const jumlah = parseFormattedNumber($(this).find('input[name="jumlah[]"]').val());
                const harga = parseFormattedNumber($(this).find('input[name="harga_beli[]"]').val());
                const totalItem = jumlah * harga;
                $(this).find('.total-item-field').val('Rp ' + new Intl.NumberFormat('id-ID').format(totalItem));
                totalHarga += totalItem;
            });

            $('#jumlah_item_display').val(new Intl.NumberFormat('id-ID').format(jumlahItem));
            $('#total_harga_display').val('Rp ' + new Intl.NumberFormat('id-ID').format(totalHarga));
        }

        function getFileThumbnail(fileType, fileUrl, fileName) {
            if (fileType === 'pdf') {
                return `
                    <div class="d-flex flex-column justify-content-center align-items-center h-100 bg-light rounded border" style="min-height: 140px;">
                        <i class="fas fa-file-pdf text-danger" style="font-size: 42px;"></i>
                        <span class="small text-muted mt-2 px-2 text-center">${fileName}</span>
                    </div>
                `;
            }

            return `<img src="${fileUrl}" alt="${fileName}" class="img-fluid rounded border" style="width: 100%; height: 140px; object-fit: cover;">`;
        }

        function openLampiranModal(fileUrl, fileType) {
            if (fileType === 'pdf') {
                $('#modalLampiran .modal-dialog')
                    .removeClass('modal-sm modal-dialog-centered modal-dialog-scrollable')
                    .addClass('modal-lg');

                $('#modalLampiran .modal-body').html(`
                    <div class="text-center">
                        <iframe src="${fileUrl}" class="w-100" style="height:80vh;"></iframe>
                    </div>
                `);
            } else {
                $('#modalLampiran .modal-dialog')
                    .removeClass('modal-lg')
                    .addClass('modal-dialog-centered modal-dialog-scrollable');

                $('#modalLampiran .modal-body').html(`
                    <div class="d-flex justify-content-center">
                        <img src="${fileUrl}" class="img-fluid" style="max-height:90vh; object-fit:contain;">
                    </div>
                `);
            }

            $('#modalLampiran').modal('show');
        }

        $(document).ready(function() {
            $('.select-supplier').select2({
                theme: 'bootstrap4',
                placeholder: 'Pilih Supplier',
                width: '100%'
            });

            addRow();
        });

        $('#addRowBtn').on('click', function() {
            addRow();
        });

        $(document).on('click', '.remove-row', function() {
            if ($('#barangTable tbody tr').length === 1) {
                return;
            }

            $(this).closest('tr').remove();
            refreshRowNumbers();
            refreshSummary();
        });

        $(document).on('change', '.select-barang', function() {
            updateSatuanField($(this));
        });

        $(document).on('click', '.btn-preview-lampiran', function() {
            openLampiranModal($(this).data('file'), $(this).data('filetype'));
        });

        $(document).on('input', '.format-number', function() {
            let value = $(this).val().replace(/[^\d]/g, '');
            $(this).val(value ? new Intl.NumberFormat('id-ID').format(value) : '');
            if ($(this).attr('name') === 'jumlah[]') {
                refreshSummary();
            }

            if ($(this).attr('name') === 'harga_beli[]') {
                refreshSummary();
            }
        });

        $(document).on('keypress', '.format-number', function(e) {
            if (e.which < 48 || e.which > 57) {
                e.preventDefault();
            }
        });

        $('#formData').on('submit', function(e) {
            e.preventDefault();

            let submitBtn = $('#submitBtn');
            let spinner = submitBtn.find('.spinner-border');
            let btnText = submitBtn.find('.button-text');

            spinner.removeClass('d-none');
            btnText.text('Menyimpan...');
            submitBtn.prop('disabled', true);

            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            let formData = new FormData(this);
            formData.append('_method', 'POST');


            $.ajax({
                url: '{{ route('barang-masuk.store') }}',
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function() {
                    sessionStorage.setItem('success', 'Data berhasil ditambahkan!');
                    window.location.href = "{{ route('barang-masuk.index') }}";
                },
                error: function(xhr) {
                    audio.play();

                    if (xhr.status === 422) {
                        toastr.error('Ada inputan yang salah!', 'GAGAL!', {
                            progressBar: true,
                            timeOut: 3500,
                            positionClass: 'toast-bottom-right',
                        });

                        let errors = xhr.responseJSON.errors || {};
                        $.each(errors, function(key, val) {
                            const field = key.split('.')[0];
                            if (['lampiran_nota', 'lampiran_surat_jalan', 'lampiran_foto_barang'].includes(field)) {
                                const picker = document.querySelector(`[name="${field}[]"]`);
                                if (picker) {
                                    const message = picker.closest('[data-receiving-upload]').querySelector('[data-upload-error]');
                                    message.textContent = val[0];
                                    message.hidden = false;
                                }
                                return;
                            }
                            let input = $('#' + key);
                            if (input.length) {
                                input.addClass('is-invalid');
                                input.parent().append(
                                    '<span class="invalid-feedback d-block" role="alert"><strong>' +
                                    val[0] + '</strong></span>'
                                );
                            }
                        });
                    } else {
                        toastr.error('Gagal menyimpan data.', 'GAGAL!', {
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
    </script>
@endpush
