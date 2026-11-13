@extends('admin.layout_admin')
@section('content')
@php
    $canSave = $week ? $permissions['edit'] : $permissions['tambah'];
    $labels = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
@endphp
<style>
    .absen-table { min-width: 1050px; }
    .absen-table thead th { background: #d9e9f5; text-align: center; vertical-align: middle; }
    .absen-table .nama { min-width: 160px; position: sticky; left: 0; background: white; z-index: 1; }
    .absen-table input { width: 78px; text-align: center; padding: 6px 3px; }
    .absen-table .total-gaji { background: #e4f0dc; white-space: nowrap; }
    .absen-table .jumlah { background: #e5f1fa; }
    .absen-table input.hadir { background: #e4f0dc; }
    .absen-table input.invalid { border-color: #dc3545; background: #fff0f0; }
    @media print { .main-sidebar, .main-header, .main-footer, .no-print { display: none !important; } .content-wrapper { margin-left: 0 !important; } .table-responsive { overflow: visible; } .absen-table { min-width: 0; width: 100%; font-size: 10px; } .absen-table input { width: 45px; border: 0; font-size: 10px; } .absen-table .nama { min-width: 90px; } }
</style>
<div class="content-wrapper">
    <section class="content-header"><h1 class="text-xl font-weight-bold">Absen &amp; Gaji Tukang</h1></section>
    <section class="content"><div class="card"><div class="card-body">
        <form method="GET" class="no-print d-flex flex-wrap align-items-end mb-3" id="weekForm">
            <div class="mr-3 mb-2"><label for="minggu">Pilih tanggal dalam pekan</label><input class="form-control" type="date" name="minggu" id="minggu" value="{{ $start->toDateString() }}" required></div>
            <button class="btn btn-primary mr-2 mb-2">Tampilkan</button>
            <a class="btn btn-outline-secondary mr-2 mb-2 week-link" href="{{ route('absen-tukang.index', ['minggu' => $start->subWeek()->toDateString()]) }}">&larr; Pekan sebelumnya</a>
            <a class="btn btn-outline-secondary mr-2 mb-2 week-link" href="{{ route('absen-tukang.index', ['minggu' => $start->addWeek()->toDateString()]) }}">Pekan berikutnya &rarr;</a>
            <button type="button" class="btn btn-outline-secondary mb-2" onclick="window.print()">Cetak</button>
        </form>
        <h5>{{ $start->translatedFormat('d M Y') }} – {{ $start->addDays(5)->translatedFormat('d M Y') }}</h5>
        <p class="text-muted no-print">Isi jam (contoh 07:30) atau M = masuk, ½ / 0.5 = setengah hari, I = izin, S = sakit, A = alpa, L = libur. Jam/M dihitung 1 hari. Kosong tidak dihitung. Tekan Tab untuk pindah sel.</p>
        <p class="small text-muted no-print">Gaji memakai tarif saat pertama kali rekap pekan disimpan. Isi gaji di Data Tukang sebelum menyimpan rekap pertama.</p>
        <div class="no-print mb-3" id="save-status" role="status">{{ $week ? 'Rekap pekan sudah tersimpan.' : 'Pekan ini belum disimpan.' }}</div>
        <div class="table-responsive"><table class="table table-bordered table-sm absen-table">
            <thead><tr><th>No</th><th>Nama Tukang</th>
                @foreach($labels as $i => $label)
                    <th>{{ $label }}<br>{{ $start->addDays($i)->format('d/m') }}
                    @if($canSave)<br><button type="button" class="btn btn-sm btn-outline-success no-print fill-day" data-day="{{ $i }}" title="Isi semua M atau kosongkan kolom hari ini" aria-pressed="false">Semua M</button>@endif</th>
                @endforeach
                <th>Jumlah<br>Masuk</th><th>Gaji / Hari</th><th>Total Gaji</th>
            </tr></thead>
            <tbody>
            @foreach($workers as $worker)
                @php
                    $record = $saved->get($worker->id);
                    $days = $record ? json_decode($record->hari, true) : array_fill(0, 6, '');
                    $rate = $record ? $record->gaji_per_hari : $worker->gaji_per_hari;
                @endphp
                <tr data-worker="{{ $worker->id }}" data-rate="{{ $rate }}">
                    <td>{{ $loop->iteration }}</td><th scope="row" class="nama">{{ $worker->nama }}</th>
                    @for($i = 0; $i < 6; $i++)
                        <td><input class="form-control form-control-sm attendance" data-day="{{ $i }}" aria-label="{{ $worker->nama }} {{ $labels[$i] }}" value="{{ $days[$i] ?? '' }}" list="attendance-options" autocomplete="off" @disabled(!$canSave)></td>
                    @endfor
                    <td class="jumlah text-center font-weight-bold">0</td><td class="text-right text-nowrap">Rp {{ number_format($rate, 0, ',', '.') }}</td><td class="total-gaji text-right font-weight-bold">Rp0</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot><tr><th colspan="10" class="text-right">TOTAL GAJI TUKANG</th><th class="text-right text-nowrap" id="grand-total">Rp0</th></tr></tfoot>
        </table></div>
        @if($workers->isEmpty())<p>Belum ada data tukang. Tambahkan melalui <a href="{{ route('tukang.index') }}">Data Tukang</a>.</p>@endif
        <datalist id="attendance-options"><option value="M">Masuk</option><option value="½">Setengah hari</option><option value="I">Izin</option><option value="S">Sakit</option><option value="A">Alpa</option><option value="L">Libur</option></datalist>
        @if($canSave && $workers->isNotEmpty())<button class="btn btn-primary mt-3 no-print" id="save-week">Simpan Absensi Pekan</button>@endif
    </div></div></section>
</div>
@endsection
@push('scripts')
<script>
$(function() {
    let dirty = false;
    const currency = n => 'Rp ' + new Intl.NumberFormat('id-ID').format(n);
    function normalize(value) {
        let v = value.trim().toUpperCase();
        if (v === 'H') v = 'M';
        const time = v.match(/^(\d{1,2})[:.](\d{2})$/);
        if (time) v = time[1].padStart(2, '0') + ':' + time[2];
        return v;
    }
    function units(v) { return v === '½' || v === '0.5' ? 0.5 : (v === 'M' || /^([01]\d|2[0-3]):[0-5]\d$/.test(v) ? 1 : 0); }
    function valid(v) { return /^(|M|I|S|A|L|½|0\.5|([01]\d|2[0-3]):[0-5]\d)$/.test(v); }
    function calculate() {
        let total = 0;
        $('[data-worker]').each(function() {
            let count = 0;
            $(this).find('.attendance').each(function() {
                const v = normalize(this.value);
                count += units(v);
                $(this).toggleClass('hadir', units(v) > 0).toggleClass('invalid', !valid(v));
            });
            const wage = count * Number($(this).data('rate'));
            $(this).find('.jumlah').text(count);
            $(this).find('.total-gaji').text(currency(wage));
            total += wage;
        });
        $('#grand-total').text(currency(total));
        $('.fill-day').each(function() {
            const cells = $('.attendance[data-day="' + $(this).data('day') + '"]:enabled');
            const allPresent = cells.length > 0 && cells.toArray().every(cell => normalize(cell.value) === 'M');
            $(this).text(allPresent ? 'Kosongkan' : 'Semua M')
                .attr('aria-pressed', String(allPresent));
        });
    }
    $('.attendance').on('input', function() { dirty = true; $('#save-status').text('Ada perubahan yang belum disimpan.'); calculate(); })
        .on('blur', function() { this.value = normalize(this.value); });
    $('.fill-day').on('click', function() {
        const cells = $('.attendance[data-day="' + $(this).data('day') + '"]:enabled');
        const allPresent = cells.length > 0 && cells.toArray().every(cell => normalize(cell.value) === 'M');
        cells.val(allPresent ? '' : 'M');
        if (cells.length) cells.first().trigger('input');
    });
    $('#weekForm').on('submit', function(e) { if (dirty && !confirm('Perubahan belum disimpan. Pindah pekan?')) e.preventDefault(); else dirty = false; });
    $('.week-link').on('click', function(e) { if (dirty && !confirm('Perubahan belum disimpan. Pindah pekan?')) e.preventDefault(); else dirty = false; });
    window.addEventListener('beforeunload', function(e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
    $('#save-week').on('click', function() {
        const rows = [];
        $('[data-worker]').each(function() { rows.push({id: $(this).data('worker'), hari: $(this).find('.attendance').map(function() { return normalize(this.value); }).get()}); });
        calculate();
        if ($('.attendance.invalid').length) { $('.attendance.invalid').first().focus(); toastr.error('Periksa jam/status yang ditandai merah.'); return; }
        const button = $(this).prop('disabled', true).text('Menyimpan...');
        $.ajax({url: @json(route('absen-tukang.store')), method: 'POST', contentType: 'application/json', headers: {'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json'},
            data: JSON.stringify({mulai: @json($start->toDateString()), versi: {{ $week->versi ?? 0 }}, rows: rows}),
            success: function() { dirty = false; location.reload(); },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                const message = errors ? Object.values(errors).flat().join(' ') : (xhr.responseJSON?.message || 'Gagal menyimpan absensi. Silakan coba lagi.');
                $('#save-status').text(message); toastr.error(message);
                button.prop('disabled', false).text('Simpan Absensi Pekan');
            }
        });
    });
    calculate();
});
</script>
@endpush
