<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Pengajuan Pembelian {{ $data->nomor }}</title>
    <style>
        @page { margin: 20mm 15mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; }
        .info td { padding: 3px 0; vertical-align: top; }
        .info .label { width: 105px; }
        .items { margin-top: 20px; table-layout: fixed; }
        .items th, .items td { border: 1px solid #555; padding: 7px 5px; overflow-wrap: break-word; }
        .items th { background: #eee; }
        .right { text-align: right; }
        .center { text-align: center; }
        .notes { white-space: pre-line; overflow-wrap: break-word; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    <h1>PENGAJUAN PEMBELIAN</h1>
    <table class="info">
        <tr><td class="label">Nomor Pengajuan</td><td>: {{ $data->nomor }}</td></tr>
        <tr><td class="label">Tanggal</td><td>: {{ \Carbon\Carbon::parse($data->tanggal)->format('d-m-Y') }}</td></tr>
        <tr><td class="label">Supplier</td><td>: {{ $data->supplier?->nama ?? '-' }}</td></tr>
    </table>
    <table class="items">
        <thead><tr><th style="width: 5%">No.</th><th style="width: 29%">Nama Barang</th><th style="width: 10%">Satuan</th><th style="width: 12%">Jumlah</th><th style="width: 20%">Harga (Rp)</th><th style="width: 24%">Subtotal (Rp)</th></tr></thead>
        <tbody>
            @forelse($lines as $line)
                <tr><td class="center">{{ $loop->iteration }}</td><td>{{ $line['nama'] }}</td><td class="center">{{ $line['satuan'] }}</td><td class="right">{{ number_format($line['jumlah'], 0, ',', '.') }}</td><td class="right">{{ number_format($line['harga'], 0, ',', '.') }}</td><td class="right">{{ number_format($line['subtotal'], 0, ',', '.') }}</td></tr>
            @empty
                <tr><td colspan="6" class="center">Belum ada rincian barang.</td></tr>
            @endforelse
        </tbody>
        <tbody><tr><th colspan="5" class="right">Total Pengajuan (Rp)</th><th class="right">{{ number_format(collect($lines)->sum('subtotal'), 0, ',', '.') }}</th></tr></tbody>
    </table>
    <p><strong>Keterangan:</strong></p>
    <div class="notes">{{ $data->keterangan ?: '-' }}</div>
</body>
</html>
