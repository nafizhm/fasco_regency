<p class="text-muted">Rekap seluruh akad selesai berdasarkan nominal Biaya KPR yang dicatat pada form akad.</p>
<div class="row">
    <div class="col-md-4 mb-3"><div class="border rounded p-3">Jumlah Akad <strong class="d-block">{{ $records->count() }}</strong></div></div>
    <div class="col-md-4 mb-3"><div class="border rounded p-3">Biaya Belum Diisi <strong class="d-block">{{ $records->whereNull('biaya_kpr')->count() }}</strong></div></div>
    <div class="col-md-4 mb-3"><div class="border rounded p-3">Total Biaya KPR <strong class="d-block text-success">Rp {{ number_format($records->sum('biaya_kpr'), 0, ',', '.') }}</strong></div></div>
</div>
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead><tr><th>No</th><th>Tanggal Akad</th><th>Pembeli</th><th>Unit</th><th>Bank KPR</th><th class="text-right">Biaya KPR</th></tr></thead>
        <tbody>
            @forelse ($records as $record)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $record->tgl_akad ? \Carbon\Carbon::parse($record->tgl_akad)->locale('id')->translatedFormat('d F Y') : 'Belum diisi' }}</td>
                    <td>{{ $record->nama_lengkap ?? 'Belum diisi' }}</td>
                    <td>{{ trim(($record->nama_cluster ?? '').' '.($record->kode_kavling ?? '')) ?: 'Belum diisi' }}</td>
                    <td>{{ $record->bank_kpr ?? 'Belum diisi' }}</td>
                    <td class="text-right text-nowrap">{{ $record->biaya_kpr !== null ? 'Rp '.number_format($record->biaya_kpr, 0, ',', '.') : 'Belum diisi' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">Belum ada data akad selesai.</td></tr>
            @endforelse
        </tbody>
        <tfoot><tr><th colspan="5" class="text-right">Total Biaya KPR</th><th class="text-right text-nowrap">Rp {{ number_format($records->sum('biaya_kpr'), 0, ',', '.') }}</th></tr></tfoot>
    </table>
</div>
