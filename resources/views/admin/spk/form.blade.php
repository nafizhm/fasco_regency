@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header"><h1 class="text-xl font-weight-bold">{{ $spk->exists ? 'Edit' : 'Tambah' }} SPK</h1></section>
    <section class="content"><div class="card"><div class="card-body">
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ $spk->exists ? route('spk.update', $spk) : route('spk.store') }}" id="spk-form">
            @csrf
            @if($spk->exists) @method('PUT') <input type="hidden" name="versi" value="{{ old('versi', $spk->versi) }}"> @endif
            <div class="row">
                <div class="form-group col-md-6"><label for="nomor">Nomor SPK</label><input class="form-control" id="nomor" name="nomor" value="{{ old('nomor', $spk->nomor) }}" placeholder="001-N/SPK-B2/FR/VII/2026" required maxlength="255"></div>
                <div class="form-group col-md-6"><label for="tanggal">Tanggal Surat</label><input class="form-control" type="date" id="tanggal" name="tanggal" value="{{ old('tanggal', $spk->tanggal?->format('Y-m-d')) }}" required></div>
            </div>
            <h5 class="font-weight-bold border-bottom pb-2">Pihak Pertama</h5>
            <div class="row">
                @foreach(['nama_perusahaan' => 'Nama Perusahaan', 'telp_perusahaan' => 'No. Telp Perusahaan', 'penandatangan' => 'Nama Penandatangan', 'kota' => 'Kota Penandatanganan'] as $field => $label)
                <div class="form-group col-md-6"><label for="{{ $field }}">{{ $label }}</label><input class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $spk->$field) }}" maxlength="{{ $field === 'telp_perusahaan' ? 50 : 255 }}" required></div>
                @endforeach
                <div class="form-group col-12"><label for="alamat_perusahaan">Alamat Perusahaan</label><textarea class="form-control" id="alamat_perusahaan" name="alamat_perusahaan" required>{{ old('alamat_perusahaan', $spk->alamat_perusahaan) }}</textarea></div>
            </div>
            <h5 class="font-weight-bold border-bottom pb-2">Pihak Kedua</h5>
            <div class="row">
                @foreach(['nama_pemborong' => 'Nama Pemborong', 'jabatan_pemborong' => 'Jabatan'] as $field => $label)
                <div class="form-group col-md-6"><label for="{{ $field }}">{{ $label }}</label><input class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $spk->$field) }}" maxlength="255" required></div>
                @endforeach
                <div class="form-group col-12"><label for="alamat_pemborong">Alamat Pemborong</label><textarea class="form-control" id="alamat_pemborong" name="alamat_pemborong" required>{{ old('alamat_pemborong', $spk->alamat_pemborong) }}</textarea></div>
            </div>
            <h5 class="font-weight-bold border-bottom pb-2">Pekerjaan</h5>
            <div class="row">
                <div class="form-group col-md-6"><label for="nama_proyek">Nama Proyek</label><input class="form-control" id="nama_proyek" name="nama_proyek" value="{{ old('nama_proyek', $spk->nama_proyek) }}" maxlength="255" required></div>
                <div class="form-group col-md-6"><label for="lama_pengerjaan">Lama Pengerjaan</label><input class="form-control" id="lama_pengerjaan" name="lama_pengerjaan" value="{{ old('lama_pengerjaan', $spk->lama_pengerjaan) }}" placeholder="Contoh: 30 hari kerja" maxlength="255" required></div>
                <div class="form-group col-md-6"><label for="tanggal_mulai">Mulai Pelaksanaan</label><input class="form-control" type="date" id="tanggal_mulai" name="tanggal_mulai" value="{{ old('tanggal_mulai', $spk->tanggal_mulai?->format('Y-m-d')) }}" required></div>
                <div class="form-group col-md-6"><label for="tanggal_selesai">Selesai Pelaksanaan (opsional)</label><input class="form-control" type="date" id="tanggal_selesai" name="tanggal_selesai" value="{{ old('tanggal_selesai', $spk->tanggal_selesai?->format('Y-m-d')) }}"></div>
                @foreach(['lokasi_proyek' => 'Lokasi Proyek', 'jenis_pekerjaan' => 'Jenis Pekerjaan', 'skema_pembayaran' => 'Skema Pembayaran'] as $field => $label)
                <div class="form-group col-12"><label for="{{ $field }}">{{ $label }}</label><textarea class="form-control" id="{{ $field }}" name="{{ $field }}" required>{{ old($field, $spk->$field) }}</textarea></div>
                @endforeach
            </div>
            <div class="d-flex justify-content-between mb-2"><h5 class="font-weight-bold">Rincian Borongan</h5><button type="button" class="btn btn-sm btn-primary" id="add-item">Tambah Item</button></div>
            <div class="table-responsive"><table class="table table-bordered" style="min-width:850px" id="items-table">
                <thead><tr><th>Item</th><th width="120">Volume</th><th width="120">Satuan</th><th width="180">Harga Satuan (Rp)</th><th width="170">Total Harga</th><th>Aksi</th></tr></thead>
                <tbody>
                @foreach(old('items', $spk->items) as $i => $item)
                    <tr>
                        <td><input class="form-control" data-field="item" name="items[{{ $i }}][item]" value="{{ $item['item'] }}" maxlength="1000" required></td>
                        <td><input class="form-control calc" data-field="volume" name="items[{{ $i }}][volume]" type="number" min="0.001" max="100000" step="0.001" value="{{ $item['volume'] }}" required></td>
                        <td><input class="form-control" data-field="satuan" name="items[{{ $i }}][satuan]" value="{{ $item['satuan'] }}" maxlength="50" required></td>
                        <td>
                            <input class="form-control price-format" type="text" inputmode="numeric" value="{{ $item['harga_satuan'] }}" aria-label="Harga satuan dalam rupiah" required>
                            <input data-field="harga_satuan" name="items[{{ $i }}][harga_satuan]" type="hidden" value="{{ $item['harga_satuan'] }}">
                        </td>
                        <td class="subtotal text-right text-nowrap">Rp0</td><td><button type="button" class="btn btn-sm btn-danger remove-item">Hapus</button></td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot><tr><th colspan="4" class="text-right">Total Rincian</th><th class="text-right text-nowrap" id="total-rincian">Rp0</th><td></td></tr></tfoot>
            </table></div>
            <div class="form-group"><label for="nilai_borongan">Nilai Borongan Setelah Pembulatan (Rp)</label><input class="form-control" type="number" id="nilai_borongan" name="nilai_borongan" min="1" max="100000000000000" step="1" value="{{ old('nilai_borongan', $spk->nilai_borongan) }}" placeholder="Kosongkan untuk memakai total rincian"><small class="text-muted">Nilai ini dipakai untuk menghitung sisa tagihan dan status pembayaran.</small></div>
            <a class="btn btn-secondary" href="{{ $spk->exists ? route('spk.show', $spk) : route('spk.index') }}">Batal</a>
            <button class="btn btn-primary" id="save-spk">Simpan SPK</button>
        </form>
    </div></div></section>
</div>
@endsection
@push('scripts')
<script>
$(function() {
    function formatPrice(input) {
        const cursor = input.selectionStart ?? input.value.length;
        const digitsBefore = input.value.slice(0, cursor).replace(/\D/g, '').length;
        const digits = input.value.replace(/\D/g, '');
        input.value = digits ? new Intl.NumberFormat('id-ID').format(Number(digits)) : '';
        $(input).siblings('[data-field="harga_satuan"]').val(digits);
        let position = 0;
        let count = 0;
        while (position < input.value.length && count < digitsBefore) {
            if (/\d/.test(input.value[position])) count++;
            position++;
        }
        input.setSelectionRange(position, position);
    }
    $('.price-format').each(function() { formatPrice(this); });
    function calculate() {
        let total = 0;
        $('#items-table tbody tr').each(function(i) {
            $(this).find('[data-field]').each(function() { this.name = 'items[' + i + '][' + this.dataset.field + ']'; });
            const subtotal = Math.round(Number($(this).find('[data-field="volume"]').val()) * Number($(this).find('[data-field="harga_satuan"]').val()));
            $(this).find('.subtotal').text('Rp ' + new Intl.NumberFormat('id-ID').format(subtotal)); total += subtotal;
        });
        $('#total-rincian').text('Rp ' + new Intl.NumberFormat('id-ID').format(total));
        $('#nilai_borongan').attr('placeholder', 'Kosong = Rp ' + new Intl.NumberFormat('id-ID').format(total));
    }
    const prototype = $('#items-table tbody tr').first().clone();
    $('#add-item').on('click', function() {
        if ($('#items-table tbody tr').length >= 100) return;
        const row = prototype.clone(); row.find('input').val(''); row.find('[data-field="volume"]').val(1); row.find('[data-field="harga_satuan"], .price-format').val(0);
        $('#items-table tbody').append(row); calculate();
    });
    $('#items-table').on('input', '.price-format', function() { formatPrice(this); calculate(); })
        .on('input', '.calc', calculate).on('click', '.remove-item', function() {
        if ($('#items-table tbody tr').length > 1) $(this).closest('tr').remove(); calculate();
    });
    $('#spk-form').on('submit', function() { $('#save-spk').prop('disabled', true).text('Menyimpan...'); });
    calculate();
});
</script>
@endpush
