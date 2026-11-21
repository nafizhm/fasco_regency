<style>
    .transaction-report { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 12px; }
    .transaction-report h2 { font-size: 18px; text-align: center; margin: 0 0 8px; }
    .transaction-report .period { text-align: center; margin: 0 0 16px; }
    .transaction-report table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .transaction-report th, .transaction-report td { border: 1px solid #bbb; padding: 7px 6px; vertical-align: top; overflow-wrap: anywhere; }
    .transaction-report thead { display: table-header-group; }
    .transaction-report thead th { background: #dce9f5; }
    .transaction-report .money { text-align: right; white-space: nowrap; }
    .transaction-report .total { font-weight: bold; background: #edf2f7; }
    .transaction-report .center { text-align: center; }
    .transaction-report tr { page-break-inside: avoid; }
    .transaction-report .summary { font-size: 13px; margin-bottom: 14px; }
    .transaction-report table.summary { width: auto; }
    .transaction-report .summary th, .transaction-report .summary td { border: 0; padding: 2px 6px !important; }
    .transaction-report .summary th { text-align: left; font-weight: normal; white-space: nowrap; padding-left: 0 !important; }
</style>
<div class="transaction-report">
    <h2>Rekap Transaksi — {{ ucfirst($report['mode']) }}</h2>
    <p class="period">{{ \Carbon\Carbon::parse($report['start'])->locale('id')->translatedFormat('d F Y') }} s.d. {{ \Carbon\Carbon::parse($report['end'])->locale('id')->translatedFormat('d F Y') }}</p>
    <p>Sumber: Pemasukan dan Pengeluaran.</p>
    <table class="summary">
        <tbody>
            <tr><th scope="row">Total Pemasukan</th><td>:</td><td class="money"><strong>Rp {{ number_format($report['income'], 0, ',', '.') }}</strong></td></tr>
            <tr><th scope="row">Total Pengeluaran</th><td>:</td><td class="money"><strong>Rp {{ number_format($report['expense'], 0, ',', '.') }}</strong></td></tr>
            <tr><th scope="row">Selisih</th><td>:</td><td class="money"><strong>Rp {{ number_format($report['income'] - $report['expense'], 0, ',', '.') }}</strong></td></tr>
        </tbody>
    </table>
    <table>
        <thead><tr><th>No</th>
            @if($report['mode'] === 'detail')<th>Tanggal</th><th>Sumber</th><th>Referensi</th>@endif
            <th>Kategori Transaksi</th>
            @if($report['mode'] === 'detail')<th>Keterangan</th>@endif
            <th class="money">Pemasukan (Rp)</th><th class="money">Pengeluaran (Rp)</th>
        </tr></thead>
        <tbody>
            @forelse($report['rows'] as $row)
                <tr><td class="center">{{ $loop->iteration }}</td>
                    @if($report['mode'] === 'detail')
                        <td>{{ \Carbon\Carbon::parse($row->tanggal)->locale('id')->translatedFormat('d F Y') }}</td><td>{{ $row->sumber }}</td><td>{{ $row->referensi ?: '-' }}</td>
                    @endif
                    <td>{{ $row->kategori }}</td>
                    @if($report['mode'] === 'detail')<td>{{ $row->keterangan ?: '-' }}</td>@endif
                    <td class="money">{{ number_format($row->pemasukan, 0, ',', '.') }}</td><td class="money">{{ number_format($row->pengeluaran, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ $report['mode'] === 'detail' ? 8 : 4 }}" class="center">Tidak ada transaksi pada periode ini.</td></tr>
            @endforelse
            <tr class="total"><td colspan="{{ $report['mode'] === 'detail' ? 6 : 2 }}">TOTAL</td><td class="money">{{ number_format($report['income'], 0, ',', '.') }}</td><td class="money">{{ number_format($report['expense'], 0, ',', '.') }}</td></tr>
        </tbody>
    </table>
</div>
