@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <h1>Piutang KPR</h1>
            <a href="{{ route('beranda.index') }}" class="btn btn-secondary btn-sm">Kembali ke Dashboard</a>
        </div>
    </section>
    <section class="content"><div class="container-fluid">
        <div class="card"><div class="card-body">
            <h3>LAPORAN PENCAIRAN TERMIN</h3>
            <p class="mb-1">Sisa piutang bank: <strong>Rp {{ number_format($rows->sum('sisa'), 0, ',', '.') }}</strong></p>
            <p class="text-muted small">Termin diurutkan berdasarkan tanggal pencairan. Persentase mengikuti nominal aktual terhadap plafon.
                Biaya KPR yang belum tercatat ditampilkan sebagai —. {{ $rows->whereNull('plafon')->count() }} customer belum memiliki plafon; belum masuk total piutang bank.</p>
            <div class="table-responsive">
                <table class="table table-bordered table-sm termin-report">
                    <thead>
                        <tr><th rowspan="3">No</th><th rowspan="3">Nama Cluster</th><th rowspan="3">Blok</th><th rowspan="3">Nama User</th><th rowspan="3">Tanggal Akad</th><th rowspan="3">Biaya KPR</th><th rowspan="3">Plafond</th><th colspan="{{ $stages * 2 }}">TERMIN BANK</th><th rowspan="3">Termin Blm Cair</th><th rowspan="3">Bank</th></tr>
                        <tr>@for($stage = 1; $stage <= $stages; $stage++)<th colspan="2">Termin {{ $stage }}</th>@endfor</tr>
                        <tr>@for($stage = 1; $stage <= $stages; $stage++)<th>Tgl</th><th>Nominal</th>@endfor</tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row['customer']->kavling?->nama_cluster ?? '—' }}</td>
                            <td>{{ $row['customer']->kavling?->kode_kavling ?? '—' }}</td>
                            <td><a href="{{ route('pembayaran.show', $row['customer']->id) }}">{{ $row['customer']->nama_lengkap }}</a></td>
                            <td>{{ $row['akad'] ? \Carbon\Carbon::parse($row['akad'])->format('d/m/Y') : '—' }}</td>
                            <td class="text-right">{{ $row['biaya_kpr'] !== null ? number_format($row['biaya_kpr'], 0, ',', '.') : '—' }}</td>
                            <td class="text-right">{{ $row['plafon'] !== null ? number_format($row['plafon'], 0, ',', '.') : 'Belum ada SP3K' }}</td>
                            @for($stage = 0; $stage < $stages; $stage++)
                                @php($payment = $row['payments']->get($stage))
                                <td>{{ $payment ? \Carbon\Carbon::parse($payment->tanggal)->format('d/m/Y') : '—' }}</td>
                                <td class="text-right">{{ $payment ? number_format($payment->nominal, 0, ',', '.') : '—' }}
                                    @if($payment && $row['plafon'] > 0)<small class="d-block text-muted">{{ (float) ($payment->persentase_termin ?? round($payment->nominal / ($payment->plafon_sp3k ?: $row['plafon']) * 100, 2)) }}%</small>@endif
                                </td>
                            @endfor
                            <td class="text-right font-weight-bold">{{ $row['sisa'] !== null ? number_format($row['sisa'], 0, ',', '.') : '—' }}</td>
                            <td>{{ $row['bank'] }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="{{ 9 + $stages * 2 }}" class="text-center">Belum ada customer KPR.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr><th colspan="5">TOTAL</th><th class="text-right">{{ number_format($rows->sum('biaya_kpr'), 0, ',', '.') }}</th><th class="text-right">{{ number_format($rows->sum('plafon'), 0, ',', '.') }}</th>
                        @for($stage = 0; $stage < $stages; $stage++)<td></td><th class="text-right">{{ number_format($rows->sum(fn ($row) => $row['payments']->get($stage)?->nominal ?? 0), 0, ',', '.') }}</th>@endfor
                        <th class="text-right">{{ number_format($rows->sum('sisa'), 0, ',', '.') }}</th><td></td>
                    </tr></tfoot>
                </table>
            </div>
        </div></div>
    </div></section>
</div>
<style>.termin-report{font-size:13px;white-space:nowrap}.termin-report thead th{background:#e7effa;text-align:center;vertical-align:middle}.termin-report tfoot{background:#f2f5f9}</style>
@endsection
