@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header"><div class="container-fluid"><h1 class="text-lg font-weight-bold">Catatan Kwitansi Customer</h1><p class="text-muted mb-0">Atur teks Note pada kwitansi sesuai kesepakatan masing-masing customer.</p></div></section>
    <section class="content"><div class="container-fluid"><div class="row">
        <div class="col-lg-8">
            @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
            @if ($errors->any())<div class="alert alert-danger" role="alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
            <div class="card card-outline card-primary">
                <div class="card-body">
                    <form method="GET" action="{{ route('customer-receipt-notes.index') }}">
                        <label for="receipt-customer">Pilih Customer</label>
                        <select id="receipt-customer" name="customer" class="form-control" onchange="this.form.submit()">
                            <option value="">Cari nama atau kode customer</option>
                            @foreach ($customers as $option)<option value="{{ $option->id }}" @selected($customer?->id === $option->id)>{{ $option->nama_lengkap }} ({{ $option->kode_customer ?: '#'.$option->id }})</option>@endforeach
                        </select>
                    </form>
                </div>
            </div>
            @if ($customer)
                <div class="card">
                    <div class="card-header"><h2 class="card-title font-weight-bold">{{ $customer->nama_lengkap }}</h2></div>
                    <form method="POST" action="{{ route('customer-receipt-notes.update', $customer->id) }}" id="receipt-notes-form">
                        @csrf @method('PUT')
                        <div class="card-body">
                            <input type="hidden" name="use_default" value="0">
                            <div class="custom-control custom-checkbox mb-3">
                                <input type="checkbox" name="use_default" value="1" id="receipt-use-default" class="custom-control-input" @checked((bool) old('use_default', $customer->catatan_kwitansi === null)) @disabled(!$canEdit)>
                                <label class="custom-control-label" for="receipt-use-default">Gunakan catatan bawaan</label>
                            </div>
                            <label for="receipt-notes">Isi Catatan</label>
                            <textarea name="notes" id="receipt-notes" rows="14" class="form-control" maxlength="10000" aria-describedby="receipt-notes-help" @readonly(!$canEdit)>{{ old('notes', $customer->catatan_kwitansi ?? $defaultText) }}</textarea>
                            <small id="receipt-notes-help" class="form-text text-muted">Langsung edit teks untuk membuat catatan khusus. Tulis satu poin per baris, tanpa nomor. Kosongkan untuk menyembunyikan Note. Maksimal 10.000 karakter.</small>
                        </div>
                        @if ($canEdit)<div class="card-footer text-right"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1" aria-hidden="true"></i> Simpan Catatan</button></div>@endif
                    </form>
                </div>
            @else
                <div class="card"><div class="card-body text-center text-muted py-5"><i class="far fa-file-alt fa-2x mb-3" aria-hidden="true"></i><p class="mb-0">Pilih customer untuk melihat dan mengubah catatan kwitansinya.</p></div></div>
            @endif
        </div>
        <div class="col-lg-4"><div class="card"><div class="card-body"><h2 class="h6 font-weight-bold">Catatan untuk customer yang dipilih</h2><p class="text-muted">Perubahan berlaku untuk seluruh cetak kwitansi customer tersebut, termasuk pembayaran yang sudah tercatat.</p><p class="mb-0 text-muted">Langsung ubah teks untuk membuat catatan khusus customer ini. Centang Gunakan catatan bawaan untuk kembali ke teks standar.</p></div></div></div>
    </div></div></section>
</div>
@endsection
@push('scripts')
<script>
$(function() {
    $('#receipt-customer').select2({theme: 'bootstrap4', placeholder: 'Cari nama atau kode customer', width: '100%', minimumResultsForSearch: 0});
    const defaultText = @json($defaultText);
    const checkbox = document.getElementById('receipt-use-default');
    const textarea = document.getElementById('receipt-notes');
    if (!checkbox || !textarea) return;
    let customText = textarea.value;
    function toggleDefault() {
        if (checkbox.checked) {
            customText = textarea.value;
            textarea.value = defaultText;
        } else {
            textarea.value = customText;
        }
        textarea.readOnly = @json(!$canEdit);
    }
    toggleDefault();
    checkbox.addEventListener('change', toggleDefault);
    textarea.addEventListener('input', function() {
        if (@json(!$canEdit)) return;
        checkbox.checked = false;
        customText = textarea.value;
    });
});
</script>
@endpush
