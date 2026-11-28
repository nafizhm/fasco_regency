                <table class="table table-bordered table-sm termin-report">
                    @if($pdfMode ?? false)
                    <colgroup>
                        <col style="width:2%"><col style="width:6%"><col style="width:3%"><col style="width:18%">
                        <col style="width:7%"><col style="width:8%"><col style="width:9%">
                        @for($stage = 0; $stage < $stages; $stage++)<col style="width:{{ 13.2 / $stages }}%"><col style="width:{{ 19.8 / $stages }}%">@endfor
                        <col style="width:9%"><col style="width:5%">
                    </colgroup>
                    @endif
                    <thead>
                        <tr><th rowspan="3" @if($pdfMode ?? false) style="width:2%" @endif>No</th><th rowspan="3" @if($pdfMode ?? false) style="width:6%" @endif>Nama Cluster</th><th rowspan="3" @if($pdfMode ?? false) style="width:3%" @endif>Blok</th><th rowspan="3" @if($pdfMode ?? false) style="width:18%" @endif>Nama User</th><th rowspan="3" @if($pdfMode ?? false) style="width:7%" @endif>Tanggal Akad</th><th rowspan="3" @if($pdfMode ?? false) style="width:8%" @endif>Biaya KPR</th><th rowspan="3" @if($pdfMode ?? false) style="width:9%" @endif>Plafond</th><th colspan="{{ $stages * 2 }}">TERMIN BANK</th><th rowspan="3" @if($pdfMode ?? false) style="width:9%" @endif>Termin Blm Cair</th><th rowspan="3" @if($pdfMode ?? false) style="width:5%" @endif>Bank</th></tr>
                        <tr>@for($stage = 1; $stage <= $stages; $stage++)<th colspan="2">Termin {{ $stage }}</th>@endfor</tr>
                        <tr>@for($stage = 1; $stage <= $stages; $stage++)<th @if($pdfMode ?? false) style="width:{{ 13.2 / $stages }}%" @endif>Tgl</th><th @if($pdfMode ?? false) style="width:{{ 19.8 / $stages }}%" @endif>Nominal</th>@endfor</tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                        <tr>
                            <td class="column-number">{{ $loop->iteration }}</td>
                            <td>{{ $row['customer']->kavling?->nama_cluster ?? '-' }}</td>
                            <td class="column-block">{{ $row['customer']->kavling?->kode_kavling ?? '-' }}</td>
                            <td class="customer-name" @if($pdfMode ?? false) style="font-size:{{ min(7, 7 * 44 / max(1, mb_strlen($row['customer']->nama_lengkap))) }}px" @endif>@if($pdfMode ?? false){{ $row['customer']->nama_lengkap }}@else<a href="{{ route('pembayaran.show', $row['customer']->id) }}">{{ $row['customer']->nama_lengkap }}</a>@endif</td>
                            <td>{{ $row['akad'] ? \Carbon\Carbon::parse($row['akad'])->format('d/m/Y') : '-' }}</td>
                            <td class="text-right">{{ $row['biaya_kpr'] !== null ? number_format($row['biaya_kpr'], 0, ',', '.') : '-' }}</td>
                            <td class="text-right plafond">{{ $row['plafon'] !== null ? number_format($row['plafon'], 0, ',', '.') : 'Belum ada SP3K' }}</td>
                            @for($stage = 0; $stage < $stages; $stage++)
                                @php($payment = $row['payments']->get($stage))
                                <td>{{ $payment ? \Carbon\Carbon::parse($payment->tanggal)->format('d/m/Y') : '-' }}</td>
                                <td class="text-right">{{ $payment ? number_format($payment->nominal, 0, ',', '.') : '-' }}
                                    @if($payment && $row['plafon'] > 0)<small class="d-block text-muted">{{ (float) ($payment->persentase_termin ?? round($payment->nominal / ($payment->plafon_sp3k ?: $row['plafon']) * 100, 2)) }}%</small>@endif
                                </td>
                            @endfor
                            <td class="text-right font-weight-bold">{{ $row['sisa'] !== null ? number_format($row['sisa'], 0, ',', '.') : '-' }}</td>
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