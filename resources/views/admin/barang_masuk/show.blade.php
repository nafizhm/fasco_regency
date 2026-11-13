@extends('admin.layout_admin')
@section('content')
    @php
        $isLunas = (int) $data->terbayar >= (int) $data->total_harga && (int) $data->total_harga > 0;
    @endphp
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
                                    <h3 class="font-weight-bold text-lg mb-0">{{ $isLunas ? 'View Barang Masuk' : 'Edit Barang Masuk' }}</h3>
                                    <a href="{{ route('barang-masuk.nota', $data->id) }}" target="_blank" class="btn btn-info btn-sm"><i class="fas fa-print"></i> Cetak Nota</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <form id="formData" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" id="primary_id" name="primary_id" value="{{ $data->id }}">

                                    <div class="row mb-3">
                                        <label for="tanggal" class="col-sm-2 col-form-label">Tanggal Masuk</label>
                                        <div class="col-sm-3">
                                            <input type="date" id="tanggal" name="tanggal" class="form-control"
                                                value="{{ $data->tanggal }}" {{ $isLunas ? 'readonly' : '' }}>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="jatuh_tempo" class="col-sm-2 col-form-label">Jatuh Tempo Pembayaran</label>
                                        <div class="col-sm-3">
                                            <input type="date" id="jatuh_tempo" name="jatuh_tempo" class="form-control" value="{{ $data->jatuh_tempo }}" {{ $isLunas ? 'readonly' : '' }} aria-describedby="jatuh-tempo-help">
                                            <small id="jatuh-tempo-help" class="form-text text-muted">Opsional. Isi sesuai kesepakatan pembayaran dengan supplier.</small>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="id_supplier" class="col-sm-2 col-form-label">Supplier</label>
                                        <div class="col-sm-4">
                                            <select class="form-select select-supplier" name="id_supplier" id="id_supplier"
                                                {{ $isLunas ? 'disabled' : '' }}>
                                                <option value=""></option>
                                                @foreach ($supplierList as $supplier)
                                                    <option value="{{ $supplier->id }}"
                                                        @selected((int) $selectedSupplierId === (int) $supplier->id)>
                                                        {{ $supplier->nama }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <label for="nama_penerima" class="col-sm-2 col-form-label">Nama Penerima</label>
                                        <div class="col-sm-4">
                                            <input type="text" id="nama_penerima" name="nama_penerima"
                                                class="form-control" value="{{ $data->nama_penerima }}"
                                                {{ $isLunas ? 'readonly' : '' }}>
                                        </div>
                                    </div>

                                    <div class="row mb-3 d-none">
                                        <label for="jumlah_item_display" class="col-sm-2 col-form-label">Jumlah Item</label>
                                        <div class="col-sm-3">
                                            <input type="text" id="jumlah_item_display" class="form-control"
                                                value="{{ number_format((int) $data->jum_item, 0, ',', '.') }}" readonly>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <label for="total_harga_display" class="col-sm-2 col-form-label">Total Harga</label>
                                        <div class="col-sm-3">
                                            <input type="text" id="total_harga_display" class="form-control"
                                                value="Rp {{ number_format((int) $data->total_harga, 0, ',', '.') }}" readonly>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <label class="col-sm-2 col-form-label">Riwayat Pembayaran</label>
                                        <div class="col-sm-10">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-sm mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th width="60px">No</th>
                                                            <th width="150px">Tanggal</th>
                                                            <th width="180px">Nominal</th>
                                                            <th>Keterangan</th>
                                                            <th width="120px">Bukti</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($data->pembayaran->sortByDesc('id')->values() as $index => $payment)
                                                            @php
                                                                $paymentExt = strtolower($payment->bukti_bayar_extension ?: '');
                                                                $paymentType = $paymentExt === 'pdf' ? 'pdf' : 'image';
                                                                $paymentUrl = $payment->bukti_bayar ? asset('assets/barang-masuk/pembayaran/' . $payment->bukti_bayar) : null;
                                                            @endphp
                                                            <tr>
                                                                <td class="text-center">{{ $index + 1 }}</td>
                                                                <td>{{ \Carbon\Carbon::parse($payment->tanggal_pembayaran)->translatedFormat('d F Y') }}</td>
                                                                <td>Rp {{ number_format((int) $payment->jumlah_bayar, 0, ',', '.') }}</td>
                                                                <td>{{ $payment->keterangan ?: '-' }}</td>
                                                                <td class="text-center">
                                                                    @if ($paymentUrl)
                                                                        <button type="button"
                                                                            class="btn btn-xs btn-outline-secondary btn-preview-payment-history"
                                                                            data-file="{{ $paymentUrl }}"
                                                                            data-filetype="{{ $paymentType }}">
                                                                            Lihat
                                                                        </button>
                                                                    @else
                                                                        <span class="text-muted">-</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5" class="text-center text-muted">Belum ada riwayat pembayaran.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="font-weight-bold mb-0">Daftar Barang</h5>
                                        @if (!$isLunas)
                                            <button type="button" class="btn btn-primary btn-sm" id="addRowBtn">
                                                <i class="fas fa-plus"></i> Tambah Barang
                                            </button>
                                        @endif
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
                                                    <th>Keterangan</th>
                                                    <th width="90px">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>

                                    <hr>

                                    <div class="row mb-3">
                                        <label class="col-sm-2 col-form-label">Lampiran</label>
                                        <div class="col-sm-10">
                                            <input type="file" id="lampiranPicker" class="d-none"
                                                accept=".jpg,.jpeg,.png,.pdf" multiple>
                                            <div class="d-flex align-items-center flex-wrap">
                                                <button type="button" class="btn btn-outline-secondary btn-sm mr-2" id="viewLampiranBtn">
                                                    <i class="fas fa-folder-open"></i> Lihat Lampiran
                                                </button>
                                                @if (!$isLunas)
                                                    <button type="button" class="btn btn-outline-primary btn-sm" id="addLampiranBtn">
                                                        <i class="fas fa-paperclip"></i> Tambah Lampiran
                                                    </button>
                                                @endif
                                            </div>
                                            <small class="form-text text-muted" id="lampiranSummaryText">
                                                Lampiran akan ditampilkan melalui modal.
                                            </small>
                                        </div>
                                    </div>

                                    @unless ($isLunas)
                                        @include('admin.barang_masuk.attachment_fields')
                                    @endunless

                                    <div class="modal-footer px-0 pb-0">
                                        @if (!$isLunas)
                                            <button type="submit" class="btn btn-primary ms-1" id="submitBtn">
                                                <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                                    aria-hidden="true"></span>
                                                <span class="button-text">Simpan</span>
                                            </button>
                                        @endif
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="modal fade" id="lampiranListModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title text-white font-weight-bold">Daftar Lampiran Barang Masuk</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="lampiranModalList" class="row"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
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

        $existingDetails = $detailList
            ->map(function ($detail) use ($barangList) {
                $barang = $barangList->firstWhere('id', $detail->id_barang);

                return [
                    'id_barang' => $detail->id_barang,
                    'jumlah' => number_format((int) $detail->jumlah, 0, ',', '.'),
                    'harga_beli' => number_format((int) $detail->harga_beli, 0, ',', '.'),
                    'keterangan' => $detail->keterangan,
                    'satuan' => $barang?->satuan?->nama_satuan ?? '',
                ];
            })
            ->values()
            ->all();

        $existingAttachments = $data->lampiran
            ->map(function ($lampiran) {
                return [
                    'id' => $lampiran->id,
                    'name' => $lampiran->file_original_name ?: $lampiran->file_name,
                    'url' => asset('assets/barang-masuk/lampiran/' . $lampiran->file_name),
                    'ext' => strtolower($lampiran->file_extension ?: pathinfo($lampiran->file_name, PATHINFO_EXTENSION)),
                    'deletable' => true,
                    'category' => ['nota' => 'Nota Pembelian', 'surat_jalan' => 'Surat Jalan', 'foto_barang' => 'Foto Barang Datang'][$lampiran->jenis_lampiran] ?? 'Lampiran Lainnya',
                ];
            })
            ->values()
            ->all();

        if (!empty($legacyAttachment)) {
            $existingAttachments[] = $legacyAttachment;
        }
    @endphp
    <script>
        const isReadOnly = @json($isLunas);
        const barangOptions = @json($barangOptions);
        const existingDetails = @json($existingDetails);
        const existingAttachments = @json($existingAttachments);
        const lampiranData = new DataTransfer();
        let lampiranObjectUrls = [];
        let deletedAttachmentIds = [];

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
                        <input type="text" name="jumlah[]" class="form-control format-number" value="${item.jumlah ?? ''}" ${isReadOnly ? 'readonly' : ''}>
                    </td>
                    <td>
                        <input type="text" class="form-control satuan-field" value="${item.satuan ?? ''}" readonly>
                    </td>
                    <td>
                        <input type="text" name="harga_beli[]" class="form-control format-number" value="${item.harga_beli ?? ''}" ${isReadOnly ? 'readonly' : ''}>
                    </td>
                    <td>
                        <input type="text" name="keterangan[]" class="form-control" value="${item.keterangan ?? ''}" ${isReadOnly ? 'readonly' : ''}>
                    </td>
                    <td class="text-center">
                        ${isReadOnly ? '' : '<button type="button" class="btn btn-danger btn-sm remove-row">Hapus</button>'}
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
                totalHarga += (jumlah * harga);
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

        $(document).on('click', '.btn-preview-payment-history', function() {
            openLampiranModal($(this).data('file'), $(this).data('filetype'));
        });

        function syncDeletedAttachmentInputs() {
            $('#deletedAttachmentInputs').remove();

            const wrapper = $('<div id="deletedAttachmentInputs"></div>');
            deletedAttachmentIds.forEach((id) => {
                wrapper.append(`<input type="hidden" name="delete_attachment_ids[]" value="${id}">`);
            });

            $('#formData').append(wrapper);
        }

        function renderLampiranPreview() {
            lampiranObjectUrls.forEach((url) => URL.revokeObjectURL(url));
            lampiranObjectUrls = [];

            const container = $('#lampiranModalList');
            container.empty();

            const existingVisibleAttachments = existingAttachments.filter((item) => !deletedAttachmentIds.includes(item.id));
            const totalLampiran = existingVisibleAttachments.length + lampiranData.files.length;
            $('#lampiranSummaryText').text(
                totalLampiran
                ? `${totalLampiran} lampiran tersedia. Klik "Lihat Lampiran" untuk membuka daftar lampiran.`
                : 'Belum ada lampiran untuk data ini.'
            );

            if (!existingVisibleAttachments.length && !lampiranData.files.length) {
                container.append(`
                    <div class="col-12">
                        <div class="alert alert-light border text-muted mb-0">Tidak ada lampiran untuk data ini.</div>
                    </div>
                `);
            }

            existingVisibleAttachments.forEach((file) => {
                    const fileType = file.ext === 'pdf' ? 'pdf' : 'image';
                    container.append(`
                        <div class="col-12 col-sm-6 col-lg-3 mb-3">
                            <div class="card h-100 shadow-sm border-info">
                                <div class="card-body">
                                    <div class="mb-3">
                                        ${getFileThumbnail(fileType, file.url, file.name)}
                                    </div>
                                    <div class="font-weight-bold text-truncate mb-2">${file.name}</div>
                                    <div class="text-muted small mb-3">${file.category ?? 'Lampiran Lainnya'}</div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <button type="button" class="btn btn-xs btn-outline-secondary btn-preview-lampiran" data-file="${file.url}" data-filetype="${fileType}">
                                            Lihat
                                        </button>
                                        ${file.deletable && !isReadOnly ? `
                                            <button type="button" class="btn btn-xs btn-danger remove-existing-lampiran" data-id="${file.id}">
                                                Hapus
                                            </button>
                                        ` : '<span class="text-muted small">Lampiran lama</span>'}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
                });

            Array.from(lampiranData.files).forEach((file, index) => {
                const url = URL.createObjectURL(file);
                lampiranObjectUrls.push(url);
                const fileType = file.type === 'application/pdf' ? 'pdf' : 'image';

                container.append(`
                    <div class="col-12 col-sm-6 col-lg-3 mb-3">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="mb-3">
                                    ${getFileThumbnail(fileType, url, file.name)}
                                </div>
                                <div class="font-weight-bold text-truncate mb-2">${file.name}</div>
                                <div class="text-muted small mb-3">${(file.size / 1024 / 1024).toFixed(2)} MB</div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <button type="button" class="btn btn-xs btn-outline-secondary btn-preview-lampiran" data-file="${url}" data-filetype="${fileType}">
                                        Lihat
                                    </button>
                                    ${isReadOnly ? '' : `<button type="button" class="btn btn-xs btn-danger remove-new-lampiran" data-index="${index}">Hapus</button>`}
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            });
        }

        $(document).ready(function() {
            $('.select-supplier').select2({
                theme: 'bootstrap4',
                placeholder: 'Pilih Supplier',
                width: '100%'
            });

            if (isReadOnly) {
                $('#id_supplier').prop('disabled', true).trigger('change.select2');
            }

            if (existingDetails.length) {
                existingDetails.forEach((item) => addRow(item));
            } else {
                addRow();
            }

            renderLampiranPreview();
        });

        $('#addRowBtn').on('click', function() {
            if (isReadOnly) {
                return;
            }
            addRow();
        });

        $('#addLampiranBtn').on('click', function() {
            if (isReadOnly) {
                return;
            }
            $('#lampiranPicker').trigger('click');
        });

        $('#viewLampiranBtn').on('click', function() {
            renderLampiranPreview();
            $('#lampiranListModal').modal('show');
        });

        $('#lampiranPicker').on('change', function() {
            if (isReadOnly) {
                this.value = '';
                return;
            }
            const selectedFile = this.files[0];
            if (!selectedFile) {
                return;
            }

            lampiranData.items.add(selectedFile);
            renderLampiranPreview();
            this.value = '';
        });

        $(document).on('click', '.remove-row', function() {
            if (isReadOnly) {
                return;
            }
            if ($('#barangTable tbody tr').length === 1) {
                return;
            }

            $(this).closest('tr').remove();
            refreshRowNumbers();
            refreshSummary();
        });

        $(document).on('change', '.select-barang', function() {
            if (isReadOnly) {
                return;
            }
            updateSatuanField($(this));
        });

        $(document).on('click', '.btn-preview-lampiran', function() {
            openLampiranModal($(this).data('file'), $(this).data('filetype'));
        });

        $(document).on('click', '.remove-existing-lampiran', function() {
            if (isReadOnly) {
                return;
            }
            const id = Number($(this).data('id'));
            if (!deletedAttachmentIds.includes(id)) {
                deletedAttachmentIds.push(id);
            }

            syncDeletedAttachmentInputs();
            renderLampiranPreview();
        });

        $(document).on('click', '.remove-new-lampiran', function() {
            if (isReadOnly) {
                return;
            }
            const removeIndex = Number($(this).data('index'));
            const updated = new DataTransfer();

            Array.from(lampiranData.files).forEach((file, index) => {
                if (index !== removeIndex) {
                    updated.items.add(file);
                }
            });

            lampiranData.items.clear();
            Array.from(updated.files).forEach((file) => lampiranData.items.add(file));

            renderLampiranPreview();
        });

        $(document).on('input', '.format-number', function() {
            if (isReadOnly) {
                return;
            }
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

            if (isReadOnly) {
                return;
            }

            let submitBtn = $('#submitBtn');
            let spinner = submitBtn.find('.spinner-border');
            let btnText = submitBtn.find('.button-text');

            spinner.removeClass('d-none');
            btnText.text('Menyimpan...');
            submitBtn.prop('disabled', true);

            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            syncDeletedAttachmentInputs();

            let id = $('#primary_id').val();
            let formData = new FormData(this);
            formData.append('_method', 'PUT');

            Array.from(lampiranData.files).forEach((file) => {
                formData.append('lampiran[]', file);
            });

            $.ajax({
                url: '{{ route('barang-masuk.update', ['barang_masuk' => ':id']) }}'.replace(':id', id),
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function() {
                    sessionStorage.setItem('success', 'Data berhasil diupdate!');
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
