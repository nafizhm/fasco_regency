<style>
    .tukang-report { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 12px; }
    .tukang-report h2 { font-size: 18px; margin: 0 0 6px; text-align: center; }
    .tukang-report .period { text-align: center; margin: 0 0 12px; }
    .tukang-report .report-note { font-size: 11px; margin: 8px 0; }
    .tukang-report table { width: 100%; border-collapse: collapse; }
    .tukang-report th, .tukang-report td { border: 1px solid #333; padding: 6px 4px; }
    .tukang-report thead { display: table-header-group; }
    .tukang-report thead th { background: #c3d9ec; text-align: center; vertical-align: middle; }
    .tukang-report tr { page-break-inside: avoid; }
    .tukang-report .attendance { background: #e1edf7; text-align: center; }
    .tukang-report .wage { background: #e3efdb; text-align: right; white-space: nowrap; }
    .tukang-report .center { text-align: center; }
    .tukang-report .right { text-align: right; }
    .tukang-report .total th, .tukang-report .total td { background: #ddd; font-weight: bold; }
    .tukang-report .grand-total th { background: #ccc; padding: 10px 5px; font-size: 15px; }
    .tukang-report small { font-size: 9px; font-weight: normal; }
</style>
<div class="tukang-report">
    <h2>{{ $report['title'] }}</h2>
    <p class="period">{{ $report['label'] }}</p>
    @if($report['type'] === 'bulanan')
        <p class="report-note">Pekan Senin–Sabtu dikelompokkan berdasarkan tanggal Sabtu pada bulan yang dipilih. Total gaji termasuk tambahan gaji.</p>
    @else
        <p class="report-note">M / H / jam = 1 hari; ½ / 0.5 = setengah hari. Total gaji = jumlah masuk × tarif tersimpan + tambahan gaji.</p>
    @endif
    <p class="report-note">Berdasarkan absensi yang sudah disimpan. Tanda — berarti data belum disimpan.</p>
    @if($report['type'] === 'bulanan')
        <table>
            <thead>
                <tr><th rowspan="2">No</th><th rowspan="2">Nama Tukang</th><th colspan="{{ count($report['columns']) }}">Jml Masuk</th><th colspan="{{ count($report['columns']) }}">TOTAL GAJI</th></tr>
                <tr>
                    @foreach($report['columns'] as $column)<th>{{ ['I', 'II', 'III', 'IV', 'V'][$loop->index] }}</th>@endforeach
                    @foreach($report['columns'] as $column)
                        <th>MINGGU {{ $loop->iteration }}<br><small>{{ $column['start']->format('d/m') }}–{{ $column['end']->format('d/m') }}
                            @if(!$column['saved'])
                                <br>Belum disimpan
                            @endif
                        </small></th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($report['rows'] as $row)
                    <tr><td class="center">{{ $loop->iteration }}</td><th class="worker-name">{{ $row['name'] }}</th>
                        @foreach($row['cells'] as $cell)<td class="attendance">{{ $cell['saved'] ? str_replace('.', ',', (string) $cell['count']) : '—' }}</td>@endforeach
                        @foreach($row['cells'] as $cell)<td class="wage">{{ $cell['saved'] ? 'Rp '.number_format($cell['wage'], 0, ',', '.') : '—' }}</td>@endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ 2 + count($report['columns']) * 2 }}" class="center">Belum ada data tukang.</td></tr>
                @endforelse
                <tr class="total"><th colspan="{{ 2 + count($report['columns']) }}">TOTAL GAJI TUKANG</th>@foreach($report['totals'] as $total)<td class="right">Rp {{ number_format($total, 0, ',', '.') }}</td>@endforeach</tr>
                <tr class="grand-total"><th colspan="{{ 2 + count($report['columns']) }}">TOTAL BULAN {{ mb_strtoupper($report['label']) }}</th><th colspan="{{ count($report['columns']) }}" class="right">Rp {{ number_format($report['grandTotal'], 0, ',', '.') }}</th></tr>
            </tbody>
        </table>
    @else
        <table>
            <thead><tr><th>No</th><th>Nama Tukang</th>
                @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $day)<th>{{ $day }}<br><small>{{ $report['columns'][0]['start']->addDays($loop->index)->format('d/m') }}</small></th>@endforeach
                <th>Jml Masuk</th><th>Gaji / Hari</th><th>Tambahan Gaji</th><th>Total Gaji</th>
            </tr></thead>
            <tbody>
                @forelse($report['rows'] as $row)
                    @php($cell = $row['cells'][0])
                    <tr><td class="center">{{ $loop->iteration }}</td><th>{{ $row['name'] }}</th>
                        @for($day = 0; $day < 6; $day++)<td class="attendance">{{ $cell['days'][$day] ?? '' }}</td>@endfor
                        <td class="attendance">{{ $cell['saved'] ? str_replace('.', ',', (string) $cell['count']) : '—' }}</td>
                        <td class="right">{{ $cell['saved'] ? 'Rp '.number_format($cell['rate'], 0, ',', '.') : '—' }}</td>
                        <td class="right">{{ $cell['saved'] ? 'Rp '.number_format($cell['bonus'], 0, ',', '.') : '—' }}</td>
                        <td class="wage">{{ $cell['saved'] ? 'Rp '.number_format($cell['wage'], 0, ',', '.') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="center">Belum ada data tukang.</td></tr>
                @endforelse
                <tr class="total"><th colspan="11" class="right">TOTAL GAJI TUKANG</th><td class="right">Rp {{ number_format($report['grandTotal'], 0, ',', '.') }}</td></tr>
            </tbody>
        </table>
        @if(!$report['columns'][0]['saved'])<p class="report-note">Absensi pekan ini belum disimpan.</p>@endif
    @endif
</div>
