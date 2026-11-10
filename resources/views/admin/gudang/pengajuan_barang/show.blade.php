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
                                <h3 class="font-weight-bold text-lg mb-0">Edit Pengajuan Barang</h3>
                            </div>
                            <div class="card-body">
                                <form id="formData">
                                    @csrf
                                    <input type="hidden" id="primary_id" name="primary_id" value="{{ $data->id }}">

                                    <div class="row mb-3">
                                        <label for="tanggal" class="col-sm-2 col-form-label">Tanggal</label>
                                        <div class="col-sm-3">
                                            <input type="date" id="tanggal" name="tanggal" class="form-control"
                                                value="{{ $data->tanggal }}">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="id_supplier" class="col-sm-2 col-form-label">Supplier</label>
                                        <div class="col-sm-4">
                                            <select class="form-select select-supplier" name="id_supplier" id="id_supplier">
                                                <option value=""></option>
                                                @foreach ($supplierList as $supplier)
                                                    <option value="{{ $supplier->id }}" @selected((int) $data->id_supplier === (int) $supplier->id)>
                                                        {{ $supplier->nama }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="status" class="col-sm-2 col-form-label">Status</label>
                                        <div class="col-sm-3">
                                            <select class="form-select select-status" name="status" id="status">
                                                <option value="0" @selected((int) $data->status === 0)>Pengajuan</option>
                                                <option value="1" @selected((int) $data->status === 1)>Disetujui</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <label for="keterangan_pengajuan" class="col-sm-2 col-form-label">Keterangan</label>
                                        <div class="col-sm-6">
                                            <textarea id="keterangan_pengajuan" name="keterangan_pengajuan" class="form-control" rows="2">{{ $data->keterangan }}</textarea>
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
                                                    <th width="180px">Harga</th>
                                                    <th>Keterangan</th>
                                                    <th width="90px">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>

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
                    'harga' => (int) ($barang->harga_beli ?? 0),
                ];
            })
            ->values();

        $existingDetails = $detailList
            ->map(function ($detail) {
                return [
                    'id_barang' => $detail->id_barang,
                    'jumlah' => number_format((int) $detail->jumlah, 0, ',', '.'),
                    'satuan' => $detail->barang?->satuan?->nama_satuan ?? '',
                    'harga_beli' => number_format((int) $detail->harga_beli, 0, ',', '.'),
                    'keterangan_detail' => $detail->keterangan,
                ];
            })
            ->values();
    @endphp
    <script>
        const barangOptions = @json($barangOptions);
        const existingDetails = @json($existingDetails);

        function barangSelectOptions(selectedId = '') {
            const options = ['<option value=""></option>'];

            barangOptions.forEach((barang) => {
                const selected = String(selectedId) === String(barang.id) ? 'selected' : '';
                options.push(
                    `<option value="${barang.id}" data-satuan="${barang.satuan}" data-harga="${barang.harga}" ${selected}>${barang.text}</option>`
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
                        <input type="text" name="keterangan_detail[]" class="form-control" value="${item.keterangan_detail ?? ''}">
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

        function updateBarangMeta(selectEl) {
            const option = selectEl.find('option:selected');
            const row = selectEl.closest('tr');
            row.find('.satuan-field').val(option.data('satuan') || '');

            const hargaInput = row.find('input[name="harga_beli[]"]');
            if (!hargaInput.val()) {
                const harga = Number(option.data('harga') || 0);
                hargaInput.val(harga ? new Intl.NumberFormat('id-ID').format(harga) : '');
            }

            refreshSummary();
        }

        function refreshSummary() {
            let totalHarga = 0;

            $('#barangTable tbody tr').each(function() {
                const jumlah = Number(String($(this).find('input[name="jumlah[]"]').val() || '').replace(/[^\d]/g, '')) || 0;
                const harga = Number(String($(this).find('input[name="harga_beli[]"]').val() || '').replace(/[^\d]/g, '')) || 0;
                totalHarga += (jumlah * harga);
            });

            $('#total_harga_display').val('Rp ' + new Intl.NumberFormat('id-ID').format(totalHarga));
        }

        function addRow(item = {}) {
            const row = $(renderRow(item));
            $('#barangTable tbody').append(row);
            initSelect2(row);

            if (item.id_barang) {
                row.find('.select-barang').val(String(item.id_barang)).trigger('change');
            } else {
                updateBarangMeta(row.find('.select-barang'));
            }

            refreshRowNumbers();
            refreshSummary();
        }

        $(document).ready(function() {
            $('.select-supplier').select2({
                theme: 'bootstrap4',
                placeholder: 'Pilih Supplier',
                width: '100%'
            });

            $('.select-status').select2({
                theme: 'bootstrap4',
                minimumResultsForSearch: Infinity,
                width: '100%'
            });

            if (existingDetails.length) {
                existingDetails.forEach((item) => addRow(item));
            } else {
                addRow();
            }
        });

        $('#addRowBtn').on('click', function() {
            addRow();
        });

        $(document).on('change', '.select-barang', function() {
            updateBarangMeta($(this));
        });

        $(document).on('click', '.remove-row', function() {
            if ($('#barangTable tbody tr').length === 1) {
                return;
            }

            $(this).closest('tr').remove();
            refreshRowNumbers();
            refreshSummary();
        });

        $(document).on('input', '.format-number', function() {
            let value = $(this).val().replace(/[^\d]/g, '');
            $(this).val(value ? new Intl.NumberFormat('id-ID').format(value) : '');
            refreshSummary();
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

            let id = $('#primary_id').val();
            let formData = new FormData(this);
            formData.append('_method', 'PUT');

            $.ajax({
                url: '{{ route('pengajuan-barang.update', ['pengajuan_barang' => ':id']) }}'.replace(':id', id),
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function() {
                    sessionStorage.setItem('success', 'Data berhasil diupdate!');
                    window.location.href = "{{ route('pengajuan-barang.index') }}";
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
