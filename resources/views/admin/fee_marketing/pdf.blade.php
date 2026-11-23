<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><title>Fee Marketing {{ $record->nomor }}</title>
<style>
@page { margin: 7mm 8mm 9mm; }
body { font-family: "Times-Roman", serif; font-size: 11pt; color:#111; line-height:1.25; }
table { width:100%; border-collapse:collapse; }
.kop { position:relative; text-align:center; border-bottom:3px double #222; padding-bottom:5px; height:72px; }
.logo { position:absolute; left:26px; top:0; width:48px; text-align:center; line-height:1; font-family:Helvetica,sans-serif; }
.logo img { width:44px; height:44px; }
.brand { color:#d6a133; font-size:9px; font-weight:bold; }
.regency { font-size:6px; color:#555; }
.company { color:#d59a51; font-size:23px; letter-spacing:1px; }
.subtitle { color:#d59a51; font-size:15px; font-weight:bold; }
.address { font-size:10.5px; margin-top:2px; }
.heading { text-align:center; margin:29px 0 37px; font-family:Helvetica,sans-serif; }
.heading h1 { margin:0 0 4px; font-size:23px; }
.heading div { font-size:14px; }
.heading .number { font-size:12px; }
.details td { padding:2px 0; vertical-align:top; }
.details .label { width:125px; }
.details .colon { width:9px; }
.fees { table-layout:fixed; margin-top:5px; font-size:10.5px; }
.fees th, .fees td, .history th, .history td { border:0.6px solid #333; padding:5px 3px; vertical-align:middle; overflow-wrap:break-word; }
.fees th, .history th { background:#e9e9e2; color:#16314c; text-align:center; font-weight:bold; }
.fees td { padding:4px 3px; }
.center { text-align:center; }
.money { text-align:right; }
.total td { background:#eee; font-weight:bold; }
.total .grand { background:#bdddda; color:#16314c; }
.note { font-size:10px; margin-top:2px; }
.history-title { margin:30px 0 4px; }
.history { width:50%; font-size:10px; table-layout:fixed; }
.history th { padding:9px 3px; }
.history td { text-align:center; }
.conditions { margin-top:20px; line-height:1.4; }
.lines { white-space:pre-line; }
.bank { margin-left:16px; font-weight:bold; }
.signatures { margin-top:26px; page-break-inside:avoid; }
.signatures td { width:33.33%; text-align:center; vertical-align:top; }
.signatures .owner { font-weight:bold; font-size:14px; }
.signatures .project { font-size:12px; font-weight:bold; }
.signature-space { height:80px; }
.stamp { padding-top:20px; }
.stamp img { width:48px; height:48px; }
.stamp .brand { width:60px; margin:0 auto; font-size:10px; line-height:1.1; }
tr { page-break-inside:avoid; }
thead { display:table-header-group; }
</style></head><body>
@php
$money = fn($v) => 'Rp'.number_format($v,0,',','.');
$rows = $d['rincian'];
$groups = [];
for ($i = 0; $i < count($rows); $i++) {
    $count = 1;
    while ($i + $count < count($rows) && $rows[$i+$count]['blok'] === $rows[$i]['blok'] && $rows[$i+$count]['harga_deal'] === $rows[$i]['harga_deal']) $count++;
    $groups[$i] = $count;
    $i += $count - 1;
}
@endphp
<div class="kop">
@if($logoData ?? null)<div class="logo"><img src="{{ $logoData }}" alt="Logo profil"></div>@endif
<div class="company">{{ strtoupper($d['perusahaan']) }}</div>
<div class="subtitle">{{ $d['subjudul'] }}</div>
<div class="address">{{ $d['alamat'] }}<br>No Hp : {{ $d['telepon'] }}</div>
</div>
<div class="heading"><h1>BUKTI PEMBAYARAN</h1><div>TANDA TERIMA PEMBAYARAN FEE MARKETING</div><div class="number">No : {{ $record->nomor }}</div></div>
<table class="details">
@foreach(['diterima_dari'=>'Sudah Terima Dari','leader_tim'=>'Nama Leader /Tim'] as $field=>$label)
<tr><td class="label">{{ $label }}</td><td class="colon">:</td><td>{{ $d[$field] }}</td></tr>
@endforeach
<tr><td class="label">Jumlah</td><td>:</td><td>Rp. {{ number_format($record->jumlah,0,',','.') }}</td></tr>
<tr><td>Terbilang</td><td>:</td><td>{{ $terbilang }}</td></tr>
@foreach(['untuk_pembayaran'=>'Untuk Pembayaran','blok_skema'=>'Blok / Skema','nama_user'=>'Nama User'] as $field=>$label)
<tr><td>{{ $label }}</td><td>:</td><td>{{ $d[$field] }}</td></tr>
@endforeach
<tr><td>Rincian Fee</td><td>:</td><td></td></tr>
</table>
<table class="fees"><colgroup><col style="width:17%"><col style="width:10%"><col style="width:11%"><col style="width:11%"><col style="width:5%"><col style="width:14%"><col style="width:12%"><col style="width:10%"><col style="width:10%"></colgroup>
<thead><tr><th style="width:17%">Blok</th><th style="width:10%">Level</th><th style="width:11%">Harga Deal</th><th style="width:11%">BLT</th><th style="width:5%">%</th><th style="width:14%">Komisi<br>(% x harga deal)</th><th style="width:12%">Total Fee</th><th style="width:10%">fee<br>sebelumnya</th><th style="width:10%">fee saat ini</th></tr></thead>
<tbody>
@foreach($rows as $i=>$row)
<tr>
@if(isset($groups[$i]))<td class="center" rowspan="{{ $groups[$i] }}">{{ $row['blok'] }}</td>@endif
<td>{{ $row['level'] }}</td>
@if(isset($groups[$i]))<td class="money" rowspan="{{ $groups[$i] }}">{{ $money($row['harga_deal']) }}</td>@endif
<td class="money">{{ $money($row['blt']) }}</td><td class="center">{{ str_replace('.',',',(string)$row['persen']) }}%</td>
@foreach(['komisi','total_fee','fee_sebelumnya','fee_saat_ini'] as $field)<td class="money">{{ $money($row[$field]) }}</td>@endforeach
</tr>
@endforeach
<tr class="total"><td colspan="3" class="center">Total fee</td><td></td><td></td><td></td><td></td><td></td><td class="money grand">{{ $money($record->jumlah) }}</td></tr>
</tbody></table>
<div class="note">{{ $d['catatan_harga'] }}</div>
<div class="history-title">Penerimaan Fee sebelumnya :</div>
<table class="history"><thead><tr><th style="width:33%">No</th><th>Tanggal</th><th>Fee Sebelumnya</th><th>Nominal</th></tr></thead><tbody>
@forelse($d['riwayat'] as $row)<tr><td>{{ $loop->iteration }}</td><td>{{ \Carbon\Carbon::parse($row['tanggal'])->format('d/m/Y') }}</td><td>{{ $row['keterangan'] }}</td><td>{{ $money($row['nominal']) }}</td></tr>
@empty<tr><td>—</td><td>—</td><td>—</td><td>—</td></tr>@endforelse
</tbody></table>
<div class="conditions">Ketentuan :<br><span class="lines">{{ $d['ketentuan'] }}</span>
<div class="bank">Bank &amp; No rekening : {{ $d['bank_rekening'] ?: '........................................' }}<br>Atas Nama &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $d['atas_nama'] ?: '........................................' }}</div>
</div>
<table class="signatures"><tr><td>{{ $d['kota'] }}, {{ \Carbon\Carbon::parse($record->tanggal)->format('d/m/Y') }}</td><td rowspan="3" class="stamp">@if($logoData ?? null)<img src="{{ $logoData }}" alt="Logo profil">@endif</td><td><span class="owner">{{ $d['perusahaan'] }}</span><br><span class="project">"{{ $d['proyek'] }}"</span><br>(Owner)</td></tr>
<tr><td class="signature-space"></td><td></td></tr>
<tr><td>({{ $d['jabatan_penerima'] }})</td><td>({{ $d['owner'] }})</td></tr></table>
</body></html>

