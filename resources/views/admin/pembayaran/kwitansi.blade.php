<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi {{ $pembayaran->no_kwitansi }}</title>
    <style>
        @page { margin: 16mm 14mm 18mm; }
        body { font-family: 'Times-Roman', serif; font-size: 12px; color: #222; line-height: 1.4; }
        table { border-collapse: collapse; width: 100%; }
        .letterhead { position: relative; text-align: center; border-bottom: 3px double #222; padding: 0 80px 10px; min-height: 70px; }
        .header-logo { position: absolute; left: 0; top: 0; max-width: 70px; max-height: 70px; }
        .signature-logo { max-width: 80px; max-height: 90px; }
        .company { font-family: 'Times-Roman', serif; font-size: 21px; font-weight: bold; color: #e98b19; }
        .subtitle { font-size: 11px; color: #e98b19; font-weight: bold; }
        h1 { text-align: center; font-family: 'Times-Roman', serif; font-size: 20px; color: #e98b19; margin: 16px 0 2px; }
        .receipt-heading { text-align: center; margin-bottom: 16px; }
        .receipt-heading strong { font-size: 12px; color: #555; }
        .details td { padding: 4px 0; vertical-align: top; }
        .label { width: 132px; }
        .colon { width: 14px; }
        .amount { font-size: 13px; font-weight: bold; }
        .words { font-style: italic; }
        .section { font-size: 11px; margin: 17px 0 7px; }
        .history { font-family: 'Helvetica', sans-serif; table-layout: fixed; font-size: 10px; }
        .history th { background: #f59e0b; color: #222; padding: 7px 5px; border: 1px solid #d6a557; }
        .history td { padding: 7px 5px; border: 1px solid #ddd; vertical-align: top; overflow-wrap: break-word; }
        .history tbody tr:nth-child(even) { background: #faf7f0; }
        .history tr.current { background: #fff2d9; }
        .history tr { page-break-inside: avoid; }
        .history thead { display: table-header-group; }
        .money { text-align: right; white-space: nowrap; }
        .total td { font-weight: bold; background: #fff2d9; border: 1px solid #d6a557; }
        .muted { color: #666; font-size: 9px; }
        .notes { margin-top: 14px; }
        .notes ol { padding-left: 17px; margin-top: 4px; }
        .closing { page-break-inside: avoid; }
        .signatures { margin-top: 24px; text-align: center; }
        .signatures td { width: 38%; vertical-align: top; }
        .signature-space { height: 48px; }
        .signature-name { text-decoration: underline; font-weight: bold; }
    </style>
</head>
<body>
    @php
        $money = fn ($value) => 'Rp. ' . number_format((float) $value, 0, ',', '.');
        $date = fn ($value) => $value ? \Carbon\Carbon::parse($value)->locale('id')->translatedFormat('d F Y') : '-';
    @endphp
    <div class="letterhead">
        @if ($logoData)<img class="header-logo" src="{{ $logoData }}" alt="Logo profil">@endif
        <div class="company">{{ $perusahaan?->nama_perusahaan ?: $lokasi?->nama_kavling ?: 'KWITANSI PEMBAYARAN' }}</div>
        <div class="subtitle">DEVELOPER DAN CONTRACTOR</div>
        <div>{{ $perusahaan?->alamat_perusahaan ?: $lokasi?->alamat ?: '' }}</div>
        @if ($perusahaan?->telp_perusahaan)<div>No. HP: {{ $perusahaan->telp_perusahaan }}</div>@endif
    </div>
    <h1>KWITANSI / BUKTI PEMBAYARAN</h1>
    <div class="receipt-heading">
        <strong>PEMBELIAN RUMAH</strong><br>
        No : {{ $pembayaran->no_kwitansi ?: '-' }}
    </div>
    <table class="details">
        <tr><td class="label">Sudah Terima Dari</td><td class="colon">:</td><td><strong>{{ $nasabah->nama_lengkap }}</strong></td></tr>
        <tr><td>No KTP</td><td>:</td><td>{{ $nasabah->nik ?: '-' }}</td></tr>
        <tr><td>Alamat</td><td>:</td><td>{{ $nasabah->alamat_domisili ?: $nasabah->alamat_ktp ?: '-' }}</td></tr>
        <tr><td>No HP</td><td>:</td><td>{{ $nasabah->no_telp ?: '-' }}</td></tr>
        <tr><td>Uang Sejumlah</td><td>:</td><td class="amount">{{ $money($pembayaran->nominal) }},-</td></tr>
        <tr><td>Terbilang</td><td>:</td><td class="words">{{ $terbilang }}</td></tr>
        <tr><td>Untuk Pembayaran</td><td>:</td><td>Pembelian 1 Unit Rumah di {{ $lokasi?->nama_kavling ?: '-' }} dengan harga Jual {{ $money($hargaJual) }},-<br>
            (harga rumah belum termasuk biaya admin Rp. 1.000.000,-)</td></tr>
        <tr><td>Blok / LT</td><td>:</td><td>{{ $kavling?->nama_cluster ? ucfirst(strtolower($kavling->nama_cluster)) . ' Kav.' . ($kavling->no ?: $kavling->kode_kavling ?: '-') : ($kavling?->kode_kavling ?: '-') }} / {{ $kavling?->luas_tanah ?? '-' }} M<sup>2</sup></td></tr>
        <tr><td>Skema Pembayaran</td><td>:</td><td>{{ $nasabah->jenis_pembelian ?: '-' }}</td></tr>
    </table>

    <h2 class="section">History Pembayaran</h2>
    <table class="history">
        <thead><tr>
            <th style="width: 5%">No.</th><th style="width: 17%">Tanggal</th>
            <th style="width: 23%">No. Kwitansi</th><th style="width: 33%">Keterangan</th>
            <th style="width: 22%">Nominal</th>
        </tr></thead>
        <tbody>
            @foreach ($history as $item)
                <tr class="{{ $item->id === $pembayaran->id ? 'current' : '' }}">
                    <td style="text-align: center">{{ $loop->iteration }}</td>
                    <td>{{ $date($item->tanggal) }}</td>
                    <td>{{ $item->no_kwitansi ?: '-' }}</td>
                    <td>{{ $item->keterangan_kategori ?: $item->kategori?->kategori ?: $item->keterangan ?: 'Pembayaran' }}</td>
                    <td class="money">{{ $money($item->nominal) }}</td>
                </tr>
            @endforeach
            <tr class="total"><td colspan="4">Total</td><td class="money">{{ $money($history->sum('nominal')) }}</td></tr>
        </tbody>
    </table>

    <div class="closing">
        <div class="notes"><strong style="color: #f00">Note :</strong>
            <ol>
                <li>Pembeli telah melakukan survey/ cek lokasi secara langsung<br>
                    dan mendapat penjelasan yang cukup tentang ukuran tanah/bangunan serta skema pembayaran.</li>
                <li>Ikatan Tanda Jadi Sebesar Rp. 11.000.000 (Biaya Admin Rp. 1.000.000)</li>
                <li>Apabila mengudurkan diri secara sepihak uang tanda jadi tidak dapat diminta kembali.</li>
                <li>Apabila transaksi berlanjut maka uang tanda jadi nya Rp. 10.000.000 diperhitungkan sebagai bagian dari uang muka</li>
                <li>Untuk skema KPR : Sudah termasuk Biaya Realisasi KPR, Biaya Pajak/BPHTB<br>
                    ( free Smartdoorlock, Kanopi ,keramik carport, pompa &amp; tandon air)</li>
                <li>Biaya Kelebihan Tanah (Jika ada) adalah sebesar Rp. 4.000.000/m2</li>
            </ol>
        </div>
        <table class="signatures">
            <tr><td>User/Pembeli</td><td rowspan="3" style="width: 24%; vertical-align: middle">@if ($logoData)<img class="signature-logo" src="{{ $logoData }}" alt="Logo profil">@endif</td><td>{{ $perusahaan?->kota_penandatangan ?: '-' }}, {{ $date($pembayaran->tanggal) }}<br>
                <strong style="color: #e98b19">{{ $perusahaan?->nama_perusahaan ?: '-' }}</strong><br>
                <span style="color: #e98b19">"{{ $lokasi?->nama_kavling ?: '-' }}"</span><br>
                {{ $perusahaan?->jabatan_penandatangan ?: 'Penerima' }}</td></tr>
            <tr><td class="signature-space"></td><td></td></tr>
            <tr><td class="signature-name">{{ $nasabah->nama_lengkap }}</td><td class="signature-name">{{ $perusahaan?->nama_penandatangan ?: '-' }}</td></tr>
        </table>
    </div>
</body>
</html>
