<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>{{ $dueOnly ? 'Piutang Jatuh Tempo' : 'Detail Piutang' }}</title>
<style>
@page {margin:10mm;} body {font-family:DejaVu Sans,sans-serif;font-size:9px;color:#222;} h1 {text-align:center;font-size:15px;margin:0 0 8px;} p {margin:3px 0;} table {width:100%;border-collapse:collapse;table-layout:fixed;margin-top:9px;} th,td {border:1px solid #777;padding:3px 3px;line-height:1.2;font-size:8px;white-space:nowrap;vertical-align:middle;} th {background:#e7effa;} thead {display:table-header-group;} tr {page-break-inside:avoid;} .money {text-align:right;white-space:nowrap;} small {font-size:8px;color:#555;} .number {padding-left:0;padding-right:0;text-align:center;font-size:7px;} .date,.status {text-align:center;}
</style></head><body>
<h1>{{ $dueOnly ? 'PIUTANG JATUH TEMPO' : 'DETAIL PIUTANG' }}</h1>
<p>Dicetak: {{ now('Asia/Jakarta')->format('d-m-Y H:i') }}</p>
<p>Sisa piutang: <strong>Rp {{ number_format($customers->sum('sisa_piutang'),0,',','.') }}</strong> dari {{ $customers->count() }} customer aktif.</p>
@if($dueOnly)<p>Hanya piutang jatuh tempo sampai {{ now('Asia/Jakarta')->format('d-m-Y') }}.</p>@endif
@if($search)<p>Pencarian: {{ $search }}</p>@endif
<p><small>Pembayaran biaya ganti nama tidak diperhitungkan sebagai pembayaran piutang.</small></p>
<table><colgroup><col style="width:{{ max(12, strlen((string) max(1, $customers->count())) * 5 + 4) }}px"><col style="width:20%"><col style="width:26%"><col style="width:12%"><col style="width:12%"><col style="width:12%"><col style="width:9%"><col style="width:7%"></colgroup>
<thead><tr><th class="number">No</th><th>Nama Unit</th><th>Nama Customer</th><th>Total Tagihan (Rp)</th><th>Total Pembayaran (Rp)</th><th>Sisa Piutang (Rp)</th><th>Jatuh Tempo</th><th>Status</th></tr></thead><tbody>
@forelse($customers as $customer)
@php
    $unit = $customer->gabungan_unit ?? (($customer->kavling->nama_cluster ?? '-').' / Blok '.($customer->kavling->kode_kavling ?? '-'));
    $unitFont = min(8, 8 * 34 / max(1, mb_strlen($unit)));
    $nameFont = min(8, 8 * 44 / max(1, mb_strlen($customer->nama_lengkap)));
@endphp
<tr><td class="number">{{ $loop->iteration }}</td><td style="font-size:{{ $unitFont }}px">{{ $unit }}</td><td style="font-size:{{ $nameFont }}px">{{ $customer->nama_lengkap }}</td>
<td class="money">{{ number_format($customer->total_tagihan,0,',','.') }}</td><td class="money">{{ number_format($customer->total_bayar ?? 0,0,',','.') }}</td><td class="money">{{ number_format($customer->sisa_piutang,0,',','.') }}</td>
<td class="date">{{ $customer->tanggal_jatuh_tempo ? \Carbon\Carbon::parse($customer->tanggal_jatuh_tempo)->format('d-m-Y') : '-' }}</td><td class="status">{{ $customer->sisa_piutang > 0 ? 'Belum Lunas' : 'Sudah Lunas' }}</td></tr>
@empty<tr><td colspan="8" style="text-align:center">Tidak ada piutang sesuai filter.</td></tr>@endforelse
<tr><th colspan="3">TOTAL</th><th class="money">{{ number_format($customers->sum('total_tagihan'),0,',','.') }}</th><th class="money">{{ number_format($customers->sum('total_bayar'),0,',','.') }}</th><th class="money">{{ number_format($customers->sum('sisa_piutang'),0,',','.') }}</th><th colspan="2"></th></tr>
</tbody></table></body></html>
