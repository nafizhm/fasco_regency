<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><title>Piutang KPR - Laporan Pencairan Termin</title>
<style>
@page {margin:10mm;} body {font-family:DejaVu Sans,sans-serif;font-size:8px;color:#222;}
h1 {font-size:16px;text-align:center;margin:0 0 10px;} p {margin:4px 0;}
table {width:100%;border-collapse:collapse;margin-top:12px;table-layout:fixed;}
th,td {border:1px solid #7a8795;padding:3px 2px;word-wrap:break-word;vertical-align:middle;font-size:6.5px;}
thead th {background:#e7effa;text-align:center;white-space:nowrap;} thead {display:table-header-group;}
tfoot {background:#f2f5f9;display:table-row-group;} tr {page-break-inside:avoid;}
.text-right {text-align:right;} .text-center {text-align:center;} .font-weight-bold {font-weight:bold;}
.column-number,.column-block {text-align:center;padding-left:0;padding-right:0;white-space:nowrap;} .customer-name,.plafond,.text-right {white-space:nowrap;}
.d-block {display:block;} .text-muted {color:#666;}
</style></head><body>
<h1>PIUTANG KPR - LAPORAN PENCAIRAN TERMIN</h1>
<p>Dicetak: {{ now('Asia/Jakarta')->format('d-m-Y H:i') }}</p>
<p>Sisa piutang bank: <strong>Rp {{ number_format($rows->sum('sisa'),0,',','.') }}</strong></p>
<p>Termin diurutkan berdasarkan tanggal pencairan. Persentase mengikuti nominal aktual terhadap plafon.</p>
<p>Biaya KPR yang belum tercatat ditampilkan sebagai -. {{ $rows->whereNull('plafon')->count() }} customer belum memiliki plafon; belum masuk total piutang bank.</p>
@include('admin.dashboard.piutang-kpr-table', ['pdfMode'=>true])
</body></html>
