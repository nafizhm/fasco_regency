<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Invoice IPL {{ $data->nomor }}</title>
<style>
@page {margin:12mm;} body {font-family:DejaVu Sans,sans-serif;font-size:10px;color:#222;}
.frame {border:1px solid #25718a;padding:15px;} table {width:100%;border-collapse:collapse;} th,td {border:1px dotted #9aaeb6;padding:9px 7px;vertical-align:middle;word-wrap:break-word;} th {background:#d6e8f5;color:#295b79;}
.kop {background:#193f61;color:white;margin-bottom:0;} .kop td {border:0;} .kop h1 {font-size:13px;margin:0;text-align:center;} .logo {width:50px;} .logo img {max-width:45px;max-height:48px;}
.identity th {width:26%;text-align:left;} .period {margin-top:30px;text-align:center;} h4 {margin:24px 0 5px;font-size:11px;} .payments {font-size:9px;} .payments td {text-align:center;} .payments th {text-align:center;} .payments .money {text-align:right;white-space:nowrap;} .total th {background:#eee;color:#111;} .bank {margin-top:25px;} .bank th {width:26%;} .signature {margin-top:20px;margin-left:60%;line-height:1.6;} thead {display:table-header-group;} tr {page-break-inside:avoid;} .bank,.signature {page-break-inside:avoid;}
</style></head><body><div class="frame">
<table class="kop"><tr><td class="logo">@if($logoData)<img src="{{ $logoData }}" alt="Logo">@endif</td><td><h1>BUKTI PEMBAYARAN IURAN PENGELOLAAN LINGKUNGAN (IPL)</h1></td></tr></table>
<p style="text-align:right;font-size:8px">Nomor: {{ $data->nomor }}</p>
@include('admin.invoice_ipl.detail')
</div></body></html>
