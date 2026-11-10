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
                                    <h3 class="font-weight-bold text-lg mb-0">Edit Material Jenis Pekerjaan</h3>
                                    <a href="{{ route('master-project.pekerjaan.index', $pekerjaan->id_master_project) }}" class="btn btn-secondary btn-sm">Kembali</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <form id="formData">
                                    @csrf
                                    <div class="row mb-3">
                                        <label for="jenis_proyek" class="col-sm-2 col-form-label">Jenis Proyek</label>
                                        <div class="col-sm-5">
                                            <input type="text" id="jenis_proyek" class="form-control"
                                                value="{{ $pekerjaan->project?->jenis_proyek }}" readonly>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="nama_pekerjaan" class="col-sm-2 col-form-label">Jenis Pekerjaan</label>
                                        <div class="col-sm-5">
                                            <input type="text" id="nama_pekerjaan" class="form-control"
                                                value="{{ $pekerjaan->nama_pekerjaan }}" readonly>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <label for="total_material" class="col-sm-2 col-form-label">Total Material</label>
                                        <div class="col-sm-4">
                                            <input type="text" id="total_material" class="form-control" value="0" readonly>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="font-weight-bold mb-0">Daftar Material</h5>
                                        <button type="button" class="btn btn-primary btn-sm" id="addRowBtn">
                                            <i class="fas fa-plus"></i> Tambah Material
                                        </button>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered w-100" id="detailTable">
                                            <thead>
                                                <tr>
                                                    <th width="50px">No</th>
                                                    <th>Barang</th>
                                                    <th width="140px">Satuan</th>
                                                    <th width="120px">Qty</th>
                                                    <th width="180px">Harga Satuan</th>
                                                    <th width="180px">Sub Total</th>
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
            ->values()
            ->all();

        $existingDetails = $details
            ->map(function ($detail) {
                return [
                    'id_barang' => $detail->id_barang,
                    'qty' => rtrim(rtrim(number_format((float) $detail->qty, 2, ',', '.'), '0'), ','),
                    'harga_satuan' => number_format((int) $detail->harga_satuan, 0, ',', '.'),
                    'satuan' => $detail->satuan,
                ];
            })
            ->values()
            ->all();
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
                        <input type="text" class="form-control satuan-field" value="${item.satuan ?? ''}" readonly>
                    </td>
                    <td>
                        <input type="text" name="qty[]" class="form-control format-decimal" value="${item.qty ?? ''}">
                    </td>
                    <td>
                        <input type="text" name="harga_satuan[]" class="form-control format-number" value="${item.harga_satuan ?? ''}">
                    </td>
                    <td>
                        <input type="text" class="form-control subtotal-field" value="0" readonly>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm remove-row">Hapus</button>
                    </td>
                </tr>
            `;
        }

        function parseRupiah(value) {
            return Number(String(value || '').replace(/[^\d]/g, '')) || 0;
        }

        function parseDecimal(value) {
            if (!value) {
                return 0;
            }

            const normalized = String(value).replace(/\./g, '').replace(',', '.');
            return Number(normalized) || 0;
        }

        function formatDecimal(value) {
            const number = Number(value || 0);

            if (Number.isInteger(number)) {
                return new Intl.NumberFormat('id-ID', {
                    maximumFractionDigits: 0
                }).format(number);
            }

            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(number);
        }

        function refreshRowNumbers() {
            $('#detailTable tbody tr').each(function(index) {
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

        function updateRow(selectEl) {
            const row = selectEl.closest('tr');
            const option = selectEl.find('option:selected');
            const satuan = option.data('satuan') || '';
            const harga = Number(option.data('harga') || 0);

            row.find('.satuan-field').val(satuan);
            if (!row.find('input[name="harga_satuan[]"]').val()) {
                row.find('input[name="harga_satuan[]"]').val(harga ? new Intl.NumberFormat('id-ID').format(harga) : '');
            }
            refreshSubtotal(row);
        }

        function refreshSubtotal(row) {
            const qty = parseDecimal(row.find('input[name="qty[]"]').val());
            const harga = parseRupiah(row.find('input[name="harga_satuan[]"]').val());
            const subtotal = qty * harga;

            row.find('.subtotal-field').val('Rp ' + new Intl.NumberFormat('id-ID').format(subtotal));
            refreshGrandTotal();
        }

        function refreshGrandTotal() {
            let total = 0;

            $('#detailTable tbody tr').each(function() {
                const qty = parseDecimal($(this).find('input[name="qty[]"]').val());
                const harga = parseRupiah($(this).find('input[name="harga_satuan[]"]').val());
                total += qty * harga;
            });

            $('#total_material').val('Rp ' + new Intl.NumberFormat('id-ID').format(total));
        }

        function addRow(item = {}) {
            const row = $(renderRow(item));
            $('#detailTable tbody').append(row);
            initSelect2(row);
            if (item.id_barang) {
                row.find('.select-barang').val(String(item.id_barang)).trigger('change');
            } else {
                refreshSubtotal(row);
            }
            refreshRowNumbers();
        }

        $(document).ready(function() {
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
            updateRow($(this));
        });

        $(document).on('input', '.format-number', function() {
            let value = $(this).val().replace(/[^\d]/g, '');
            $(this).val(value ? new Intl.NumberFormat('id-ID').format(value) : '');
            refreshSubtotal($(this).closest('tr'));
        });

        $(document).on('input', '.format-decimal', function() {
            let value = $(this).val().replace(/[^0-9,]/g, '');
            const parts = value.split(',');

            if (parts.length > 2) {
                value = parts[0] + ',' + parts[1];
            }

            $(this).val(value);
            refreshSubtotal($(this).closest('tr'));
        });

        $(document).on('click', '.remove-row', function() {
            if ($('#detailTable tbody tr').length === 1) {
                return;
            }

            $(this).closest('tr').remove();
            refreshRowNumbers();
            refreshGrandTotal();
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
            formData.append('_method', 'PUT');

            $.ajax({
                url: '{{ route('master-project.material.update', ['pekerjaan' => $pekerjaan->id]) }}',
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function() {
                    sessionStorage.setItem('success', 'Material project berhasil disimpan!');
                    window.location.href = "{{ route('master-project.pekerjaan.index', $pekerjaan->id_master_project) }}";
                },
                error: function(xhr) {
                    audio.play();

                    if (xhr.status === 422) {
                        toastr.error('Ada inputan yang salah!', 'GAGAL!', {
                            progressBar: true,
                            timeOut: 3500,
                            positionClass: 'toast-bottom-right',
                        });

                    } else {
                        toastr.error('Gagal menyimpan material project.', 'GAGAL!', {
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
