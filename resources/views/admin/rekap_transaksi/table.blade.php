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
</style>
<div class="transaction-report">
    <h2>Rekap Transaksi — {{ ucfirst($report['mode']) }}</h2>
    <p class="period">{{ \Carbon\Carbon::parse($report['start'])->locale('id')->translatedFormat('d F Y') }} s.d. {{ \Carbon\Carbon::parse($report['end'])->locale('id')->translatedFormat('d F Y') }}</p>
    <p>Sumber: Pemasukan, Pengeluaran, pembayaran Barang Masuk, dan pembayaran SPK.</p>
    <p class="summary">Total Pemasukan: <strong>Rp {{ number_format($report['income'], 0, ',', '.') }}</strong><br>Total Pengeluaran: <strong>Rp {{ number_format($report['expense'], 0, ',', '.') }}</strong><br>Selisih: <strong>Rp {{ number_format($report['income'] - $report['expense'], 0, ',', '.') }}</strong></p>
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
