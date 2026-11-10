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
                                    <h3 class="font-weight-bold text-lg">Data Barang Masuk</h3>
                                    <div class="d-flex align-items-center">
                                        @if ($permissions['tambah'])
                                            <a href="{{ route('barang-masuk.create') }}" class="btn btn-sm btn-primary mr-2"><i
                                                    class="fas fa-plus"></i> Tambah Barang Masuk</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-striped data-table">
                                    <thead>
                                        <tr>
                                            <th width="50px">No</th>
                                            <th>Tanggal</th>
                                            <th>Supplier</th>
                                            <th>Penerima</th>
                                            <th width="100px">Jml Item</th>
                                            <th width="170px">Total Harga</th>
                                            <th width="140px">Status</th>
                                            <th width="180px">Action</th>
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
    </div>

    <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form id="paymentForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Pembayaran Barang Masuk</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="payment_barang_masuk_id">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Supplier</label>
                                <input type="text" id="payment_supplier" class="form-control" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jumlah Item</label>
                                <input type="text" id="payment_jum_item" class="form-control" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <input type="text" id="payment_status" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Total Harga</label>
                                <input type="text" id="payment_total_harga" class="form-control" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sudah Dibayar</label>
                                <input type="text" id="payment_terbayar" class="form-control" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sisa Bayar</label>
                                <input type="text" id="payment_sisa" class="form-control" readonly>
                            </div>
                        </div>

                        <hr>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="tanggal_pembayaran" class="form-label">Tgl Pembayaran</label>
                                <input type="date" id="tanggal_pembayaran" name="tanggal_pembayaran" class="form-control"
                                    value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="jumlah_bayar" class="form-label">Jumlah Bayar</label>
                                <input type="text" id="jumlah_bayar" name="jumlah_bayar" class="form-control format-number">
                            </div>
                            <div class="col-md-4">
                                <label for="bukti_bayar" class="form-label">Upload Bukti Bayar</label>
                                <input type="file" id="bukti_bayar" name="bukti_bayar" class="form-control"
                                    accept=".jpg,.jpeg,.png,.pdf">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="keterangan_pembayaran" class="form-label">Keterangan</label>
                                <textarea id="keterangan_pembayaran" name="keterangan_pembayaran" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-3">
                                <label class="form-label d-block">Preview Bukti</label>
                                <div id="paymentProofPreview"
                                    class="border rounded d-flex align-items-center justify-content-center bg-light"
                                    style="height: 120px;">
                                    <span class="text-muted">Belum ada file</span>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <label class="form-label d-block">Riwayat Pembayaran</label>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Jumlah</th>
                                                <th>Keterangan</th>
                                                <th width="90px">Bukti</th>
                                            </tr>
                                        </thead>
                                        <tbody id="paymentHistoryBody">
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">Belum ada pembayaran.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary" id="submitPaymentBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>
                            <span class="button-text">Simpan Pembayaran</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        let currentPaymentUrl = '';

        $(document).ready(function() {
            const successMsg = sessionStorage.getItem('success');
            if (successMsg) {
                audio.play();
                toastr.success(successMsg, "BERHASIL", {
                    progressBar: true,
                    timeOut: 3500,
                    positionClass: "toast-bottom-right",
                });
                sessionStorage.removeItem('success');
            }
        });

        var permissions = @json($permissions);
        var showActionColumn = (permissions['edit'] == 1 || permissions['hapus'] == 1);

        $(function() {
            var table = $('.data-table').DataTable({
                processing: false,
                serverSide: true,
                ordering: false,
                responsive: true,
                ajax: "{{ route('barang-masuk.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'tanggal',
                        name: 'tanggal',
                        searchable: false
                    },
                    {
                        data: 'supplier',
                        name: 'supplier',
                        searchable: false
                    },
                    {
                        data: 'nama_penerima',
                        name: 'nama_penerima',
                        searchable: false
                    },
                    {
                        data: 'jum_item',
                        name: 'jum_item',
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'total_harga',
                        name: 'total_harga',
                        searchable: false
                    },
                    {
                        data: 'status_pembayaran',
                        name: 'status_pembayaran',
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        visible: showActionColumn,
                        className: 'text-center'
                    },
                ],
                columnDefs: [{
                    targets: 0,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                }]
            });
        });

        function paymentPreviewHtml(fileUrl, isPdf) {
            if (!fileUrl) {
                return '<span class="text-muted">Belum ada file</span>';
            }

            if (isPdf) {
                return `
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-preview-payment-file" data-file="${fileUrl}" data-filetype="pdf">
                        <i class="fas fa-file-pdf text-danger"></i> Lihat PDF
                    </button>
                `;
            }

            return `
                <button type="button" class="btn p-0 border-0 bg-transparent btn-preview-payment-file" data-file="${fileUrl}" data-filetype="image">
                    <img src="${fileUrl}" class="img-fluid rounded" style="max-height: 110px; object-fit: cover;">
                </button>
            `;
        }

        function showPaymentPreview(fileUrl, fileType) {
            $('#paymentProofPreview').html(paymentPreviewHtml(fileUrl, fileType === 'pdf'));
        }

        function openPaymentFileModal(fileUrl, fileType) {
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

        function renderPaymentHistory(payments) {
            const tbody = $('#paymentHistoryBody');
            tbody.empty();

            if (!payments.length) {
                tbody.html(`
                    <tr>
                        <td colspan="4" class="text-center text-muted">Belum ada pembayaran.</td>
                    </tr>
                `);
                return;
            }

            payments.forEach((payment) => {
                const buktiButton = payment.bukti_url ?
                    `<button type="button" class="btn btn-xs btn-outline-secondary btn-show-history-proof" data-file="${payment.bukti_url}" data-filetype="${payment.bukti_type}">Lihat</button>` :
                    '<span class="text-muted">-</span>';

                tbody.append(`
                    <tr>
                        <td>${payment.tanggal_pembayaran}</td>
                        <td>Rp ${payment.jumlah_bayar}</td>
                        <td>${payment.keterangan}</td>
                        <td class="text-center">${buktiButton}</td>
                    </tr>
                `);
            });
        }

        function resetPaymentForm() {
            $('#paymentForm')[0].reset();
            $('#payment_barang_masuk_id').val('');
            $('#tanggal_pembayaran').val('{{ date('Y-m-d') }}');
            $('#paymentProofPreview').html('<span class="text-muted">Belum ada file</span>');
            $('#paymentHistoryBody').html(`
                <tr>
                    <td colspan="4" class="text-center text-muted">Belum ada pembayaran.</td>
                </tr>
            `);
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
        }

        $(document).on('change', '#bukti_bayar', function() {
            const file = this.files[0];
            const preview = $('#paymentProofPreview');

            if (!file) {
                preview.html('<span class="text-muted">Belum ada file</span>');
                return;
            }

            if (file.type === 'application/pdf') {
                const url = URL.createObjectURL(file);
                preview.html(paymentPreviewHtml(url, true));
                return;
            }

            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.html(paymentPreviewHtml(e.target.result, false));
                };
                reader.readAsDataURL(file);
                return;
            }

            preview.html('<span class="text-muted">File tidak didukung</span>');
        });

        $(document).on('click', '.btn-preview-payment-file', function() {
            openPaymentFileModal($(this).data('file'), $(this).data('filetype'));
        });

        $(document).on('click', '.btn-show-history-proof', function() {
            showPaymentPreview($(this).data('file'), $(this).data('filetype'));
        });

        $(document).on('click', '.btn-open-payment', function() {
            const id = $(this).data('id');
            currentPaymentUrl = $(this).data('payment-url');
            resetPaymentForm();

            $('#payment_barang_masuk_id').val(id);
            $('#payment_supplier').val($(this).data('supplier'));

            $.get(`{{ route('barang-masuk.payment-info', ['barang_masuk' => ':id']) }}`.replace(':id', id), function(response) {
                const data = response.data;
                $('#payment_jum_item').val(new Intl.NumberFormat('id-ID').format(data.jum_item));
                $('#payment_total_harga').val('Rp ' + new Intl.NumberFormat('id-ID').format(data.total_harga));
                $('#payment_terbayar').val('Rp ' + new Intl.NumberFormat('id-ID').format(data.terbayar));
                $('#payment_sisa').val('Rp ' + new Intl.NumberFormat('id-ID').format(data.sisa));
                $('#payment_status').val(data.status);
                renderPaymentHistory(data.pembayaran || []);
                if (data.pembayaran && data.pembayaran.length && data.pembayaran[0].bukti_url) {
                    showPaymentPreview(data.pembayaran[0].bukti_url, data.pembayaran[0].bukti_type);
                }
                $('#paymentModal').modal('show');
            });
        });

        $('#paymentForm').on('submit', function(e) {
            e.preventDefault();

            const submitBtn = $('#submitPaymentBtn');
            const spinner = submitBtn.find('.spinner-border');
            const btnText = submitBtn.find('.button-text');

            spinner.removeClass('d-none');
            btnText.text('Menyimpan...');
            submitBtn.prop('disabled', true);

            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            const formData = new FormData(this);

            $.ajax({
                url: currentPaymentUrl,
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    audio.play();
                    toastr.success(response.message || 'Pembayaran berhasil disimpan.', 'BERHASIL', {
                        progressBar: true,
                        timeOut: 3500,
                        positionClass: 'toast-bottom-right',
                    });

                    $('#paymentModal').modal('hide');
                    $('.data-table').DataTable().ajax.reload(null, false);
                },
                error: function(xhr) {
                    audio.play();

                    if (xhr.status === 422) {
                        toastr.error('Ada inputan pembayaran yang salah!', 'GAGAL!', {
                            progressBar: true,
                            timeOut: 3500,
                            positionClass: 'toast-bottom-right',
                        });

                        const errors = xhr.responseJSON.errors || {};
                        $.each(errors, function(key, val) {
                            const input = $('#' + key);
                            if (input.length) {
                                input.addClass('is-invalid');
                                input.after(
                                    '<span class="invalid-feedback d-block" role="alert"><strong>' +
                                    val[0] + '</strong></span>'
                                );
                            }
                        });
                    } else {
                        toastr.error('Gagal menyimpan pembayaran.', 'GAGAL!', {
                            progressBar: true,
                            timeOut: 3500,
                            positionClass: 'toast-bottom-right',
                        });
                    }
                },
                complete: function() {
                    spinner.addClass('d-none');
                    btnText.text('Simpan Pembayaran');
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
