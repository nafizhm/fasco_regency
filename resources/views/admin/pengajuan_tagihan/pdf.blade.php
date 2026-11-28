<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><title>Pengajuan Tagihan</title>
<style>
@page { margin: 10mm; }
body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #222; }
h1 { text-align: center; font-size: 17px; margin: 0 0 12px; }
.filters { margin-bottom: 10px; }
table { width: 100%; border-collapse: collapse; table-layout: fixed; }
th, td { border: 1px solid #666; padding: 5px 3px; vertical-align: top; overflow-wrap: break-word; word-wrap: break-word; }
th { background: #e8edf3; }
thead { display: table-header-group; }
tr { page-break-inside: avoid; }
.money { text-align: right; white-space: nowrap; font-size: 7px; }
.notes { white-space: pre-line; }
.total th { background: #dce5f0; }
</style></head><body>
<h1>PENGAJUAN TAGIHAN</h1>
<div class="filters">Status: {{ $status ?: 'Semua status' }}@if($search) | Pencarian: {{ $search }}@endif<br>Dicetak: {{ now('Asia/Jakarta')->format('d-m-Y H:i') }} | Jumlah data: {{ $lines->count() }}</div>
<table>
<colgroup><col style="width:12%"><col style="width:7%"><col style="width:10%"><col style="width:14%">@foreach(\App\Models\PengajuanTagihan::LOKASI as $label)<col style="width:7%">@endforeach<col style="width:8%"><col style="width:14%"></colgroup>
<thead><tr><th>Nomor / Tanggal pengajuan</th><th>Status</th><th>Nama</th><th>Uraian</th>@foreach(\App\Models\PengajuanTagihan::LOKASI as $label)<th>{{ $label }} (Rp)</th>@endforeach<th>Total (Rp)</th><th>No rekening</th></tr></thead>
<tbody>
@forelse($lines as $line)
<tr><td>{{ $line->pengajuan->nomor }}<br>{{ \Carbon\Carbon::parse($line->pengajuan->tanggal)->format('d-m-Y') }}</td><td>{{ $line->pengajuan->status }}</td><td>{{ $line->nama }}</td><td class="notes">{{ $line->uraian }}</td>@foreach(\App\Models\PengajuanTagihan::LOKASI as $key => $label)<td class="money">{{ number_format($line->$key, 0, ',', '.') }}</td>@endforeach<td class="money">{{ number_format($line->total, 0, ',', '.') }}</td><td>{{ $line->rekening ?: '-' }}</td></tr>
@empty<tr><td colspan="11" style="text-align:center">Tidak ada tagihan sesuai filter.</td></tr>@endforelse
</tbody>
<tbody><tr class="total"><th colspan="4">TOTAL (Rp)</th>@foreach(\App\Models\PengajuanTagihan::LOKASI as $key => $label)<th class="money">{{ number_format($lines->sum($key), 0, ',', '.') }}</th>@endforeach<th class="money">{{ number_format($lines->sum('total'), 0, ',', '.') }}</th><th></th></tr></tbody>
</table>
</body></html>
