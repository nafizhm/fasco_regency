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
                                    <h3 class="font-weight-bold text-lg mb-0">Edit Barang Keluar {{ $mode === 'non-unit' ? 'Non Unit' : 'Unit' }}</h3>
                                    <a href="{{ route('barang-keluar.nota', $data->id) }}" target="_blank" class="btn btn-info btn-sm"><i class="fas fa-print"></i> Cetak Nota</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <form id="formData">
                                    @csrf
                                    <input type="hidden" id="primary_id" name="primary_id" value="{{ $data->id }}">
                                    <input type="hidden" name="mode" id="mode" value="{{ $mode }}">

                                    <div class="row mb-3">
                                        <label for="tanggal" class="col-sm-2 col-form-label">Tanggal Keluar</label>
                                        <div class="col-sm-3">
                                            <input type="date" id="tanggal" name="tanggal" class="form-control"
                                                value="{{ $data->tanggal }}">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="id_lokasi" class="col-sm-2 col-form-label">Lokasi Perumahan</label>
                                        <div class="col-sm-4">
                                            <select class="form-select select-lokasi" name="id_lokasi" id="id_lokasi">
                                                <option value=""></option>
                                                @foreach ($lokasiList as $lokasi)
                                                    <option value="{{ $lokasi->id }}"
                                                        @selected((int) $data->id_lokasi === (int) $lokasi->id)>
                                                        {{ $lokasi->nama_kavling }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    @if ($mode === 'unit')
                                        <div class="row mb-3">
                                            <label for="id_kavling" class="col-sm-2 col-form-label">Lokasi Unit</label>
                                            <div class="col-sm-4">
                                                <select class="form-select select-kavling" name="id_kavling" id="id_kavling">
                                                    <option value=""></option>
                                                    @foreach ($unitList as $unit)
                                                        <option value="{{ $unit->id_kavling }}"
                                                            data-project-id="{{ $unit->id_master_project ?? '' }}"
                                                            @selected((int) $data->id_kavling === (int) $unit->id_kavling)>
                                                            {{ $unit->kode_kavling }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    @else
                                        <div class="row mb-3">
                                            <label for="id_project" class="col-sm-2 col-form-label">Master Proyek</label>
                                            <div class="col-sm-4">
                                                <select class="form-select select-project" name="id_project" id="id_project">
                                                    <option value=""></option>
                                                    @foreach ($nonUnitProjectList as $project)
                                                        <option value="{{ $project->id }}" @selected((int) $data->id_project === (int) $project->id)>
                                                            {{ $project->jenis_proyek }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="row mb-4">
                                        <label for="id_tukang" class="col-sm-2 col-form-label">Nama Tukang</label>
                                        <div class="col-sm-4">
                                            <select class="form-select select-tukang" name="id_tukang" id="id_tukang">
                                                <option value=""></option>
                                                @foreach ($tukangList as $tukang)
                                                    <option value="{{ $tukang->id }}" @selected((int) $data->id_tukang === (int) $tukang->id)>
                                                        {{ $tukang->nama }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <label for="keterangan" class="col-sm-2 col-form-label">Keterangan</label>
                                        <div class="col-sm-6">
                                            <textarea id="keterangan" name="keterangan" class="form-control" rows="2">{{ $data->keterangan }}</textarea>
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
                                                    <th>Jenis Pekerjaan</th>
                                                    <th width="140px">Stok</th>
                                                    <th width="140px">Jumlah</th>
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
                    'stok' => (int) $barang->stok,
                ];
            })
            ->values();

        $existingDetails = $detailList
            ->map(function ($detail) use ($barangList) {
                return [
                    'id_barang' => $detail->id_barang,
                    'id_master_project_pekerjaan' => $detail->id_master_project_pekerjaan,
                    'jumlah' => number_format((int) $detail->jumlah, 0, ',', '.'),
                    'stok' => number_format(
                        (int) (optional($barangList->firstWhere('id', $detail->id_barang))->stok ?? 0),
                        0,
                        ',',
                        '.'
                    ),
                    'keterangan_detail' => $detail->keterangan,
                ];
            })
            ->values();

        $pekerjaanOptions = $pekerjaanList
            ->map(function ($pekerjaan) {
                return [
                    'id' => $pekerjaan->id,
                    'nama_pekerjaan' => $pekerjaan->nama_pekerjaan,
                ];
            })
            ->values();
    @endphp
    <script>
        const barangOptions = @json($barangOptions);
        const existingDetails = @json($existingDetails);
        let pekerjaanOptions = @json($pekerjaanOptions);

        function barangSelectOptions(selectedId = '') {
            const options = ['<option value=""></option>'];

            barangOptions.forEach((barang) => {
                const selected = String(selectedId) === String(barang.id) ? 'selected' : '';
                options.push(`<option value="${barang.id}" data-stok="${barang.stok}" ${selected}>${barang.text}</option>`);
            });

            return options.join('');
        }

        function pekerjaanSelectOptions(selectedId = '') {
            const options = ['<option value=""></option>'];

            pekerjaanOptions.forEach((pekerjaan) => {
                const selected = String(selectedId) === String(pekerjaan.id) ? 'selected' : '';
                options.push(`<option value="${pekerjaan.id}" ${selected}>${pekerjaan.nama_pekerjaan}</option>`);
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
                        <select class="form-select select-pekerjaan" name="id_master_project_pekerjaan[]">
                            ${pekerjaanSelectOptions(item.id_master_project_pekerjaan ?? '')}
                        </select>
                    </td>
                    <td class="text-center align-middle stok-cell">${item.stok ?? '-'}</td>
                    <td>
                        <input type="text" name="jumlah[]" class="form-control format-number" value="${item.jumlah ?? ''}">
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

        function initBarangSelect(scope) {
            scope.find('.select-barang').select2({
                theme: 'bootstrap4',
                placeholder: 'Pilih Barang',
                width: '100%'
            });

            scope.find('.select-pekerjaan').select2({
                theme: 'bootstrap4',
                placeholder: 'Pilih Jenis Pekerjaan',
                width: '100%'
            });
        }

        function updateStockLabel(selectEl) {
            const option = selectEl.find('option:selected');
            const stok = option.data('stok');
            selectEl.closest('tr').find('.stok-cell').text(stok !== undefined && stok !== '' ? new Intl.NumberFormat('id-ID').format(stok) : '-');
        }

        function addRow(item = {}) {
            const row = $(renderRow(item));
            $('#barangTable tbody').append(row);
            initBarangSelect(row);
            if (item.id_barang) {
                row.find('.select-barang').val(String(item.id_barang)).trigger('change');
            }
            if (item.id_master_project_pekerjaan) {
                row.find('.select-pekerjaan').val(String(item.id_master_project_pekerjaan)).trigger('change');
            }
            refreshRowNumbers();
        }

        function loadKavlingOptions(lokasiId, selectedId = '') {
            $('#id_kavling').empty().append('<option value=""></option>');
            if (!lokasiId) {
                $('#id_kavling').trigger('change');
                return;
            }

            $.get('{{ route('barang-keluar.unit-by-lokasi', ['id' => ':id']) }}'.replace(':id', lokasiId), function(response) {
                response.forEach(function(item) {
                    const selected = String(selectedId) === String(item.id_kavling) ? 'selected' : '';
                    $('#id_kavling').append(
                        `<option value="${item.id_kavling}" data-project-id="${item.id_master_project || ''}" ${selected}>${item.kode_kavling}</option>`
                    );
                });
                $('#id_kavling').trigger('change');
            });
        }

        function refreshPekerjaanOptions() {
            $('#barangTable tbody tr').each(function() {
                const select = $(this).find('.select-pekerjaan');
                const currentValue = select.val();
                select.html(pekerjaanSelectOptions(currentValue)).trigger('change.select2');
            });
        }

        function loadPekerjaanOptions(projectId) {
            pekerjaanOptions = [];
            refreshPekerjaanOptions();

            if (!projectId) {
                return;
            }

            $.get('{{ route('barang-keluar.pekerjaan-by-project', ['id' => ':id']) }}'.replace(':id', projectId), function(response) {
                pekerjaanOptions = response || [];
                refreshPekerjaanOptions();
            });
        }

        $(document).ready(function() {
            $('#id_lokasi, #id_kavling, #id_tukang, #id_project').select2({
                theme: 'bootstrap4',
                width: '100%',
            });

            $('#id_lokasi').select2({
                theme: 'bootstrap4',
                placeholder: 'Pilih Lokasi Perumahan',
                width: '100%',
            });

            if ($('#id_kavling').length) {
                $('#id_kavling').select2({
                    theme: 'bootstrap4',
                    placeholder: 'Pilih Lokasi Unit',
                    width: '100%',
                });
            }

            if ($('#id_project').length) {
                $('#id_project').select2({
                    theme: 'bootstrap4',
                    placeholder: 'Pilih Master Proyek Non Unit',
                    width: '100%',
                });
            }

            $('#id_tukang').select2({
                theme: 'bootstrap4',
                placeholder: 'Pilih Tukang',
                width: '100%',
            });

            if (existingDetails.length) {
                existingDetails.forEach((item) => addRow(item));
            } else {
                addRow();
            }
        });

        if ($('#id_kavling').length) {
            $('#id_lokasi').on('change', function() {
                loadKavlingOptions($(this).val());
            });

            $('#id_kavling').on('change', function() {
                loadPekerjaanOptions($(this).find('option:selected').data('project-id'));
            });
        }

        if ($('#id_project').length) {
            $('#id_project').on('change', function() {
                loadPekerjaanOptions($(this).val());
            });
        }

        $('#addRowBtn').on('click', function() {
            addRow();
        });

        $(document).on('change', '.select-barang', function() {
            updateStockLabel($(this));
        });

        $(document).on('click', '.remove-row', function() {
            if ($('#barangTable tbody tr').length === 1) {
                return;
            }

            $(this).closest('tr').remove();
            refreshRowNumbers();
        });

        $(document).on('input', '.format-number', function() {
            let value = $(this).val().replace(/[^\d]/g, '');
            $(this).val(value ? new Intl.NumberFormat('id-ID').format(value) : '');
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

            let id = $('#primary_id').val();
            let formData = new FormData(this);
            formData.append('_method', 'PUT');

            $.ajax({
                url: '{{ route('barang-keluar.update', ['barang_keluar' => ':id']) }}'.replace(':id', id),
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function() {
                    sessionStorage.setItem('success', 'Data berhasil diupdate!');
                    window.location.href = "{{ route('barang-keluar.index') }}";
                },
                error: function(xhr) {
                    audio.play();
                    const errorMessage = xhr.responseJSON?.message || 'Gagal menyimpan data.';

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
                                input.parent().append('<span class="invalid-feedback d-block" role="alert"><strong>' + val[0] + '</strong></span>');
                            }
                        });
                    } else {
                        toastr.error(errorMessage, 'GAGAL!', {
                            progressBar: true,
                            timeOut: 4000,
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
