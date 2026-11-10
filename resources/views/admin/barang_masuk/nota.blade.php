<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Barang Masuk - {{ $nota['no_nota'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 13px;
            color: #000;
            padding: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h2 {
            font-size: 22px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .header p {
            font-size: 12px;
        }

        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 15px;
        }

        .info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .info table {
            font-size: 13px;
        }

        .info td {
            padding: 2px 8px 2px 0;
        }

        table.detail {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table.detail th,
        table.detail td {
            border: 1px solid #000;
            padding: 5px 8px;
            text-align: left;
        }

        table.detail th {
            background: #f0f0f0;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-row td {
            font-weight: bold;
        }

        .terbilang {
            margin-bottom: 15px;
            font-style: italic;
        }

        .pembayaran {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .pembayaran th,
        .pembayaran td {
            border: 1px solid #000;
            padding: 5px 8px;
        }

        .signature {
            display: flex;
            justify-content: flex-end;
            margin-top: 40px;
        }

        .signature div {
            text-align: center;
            width: 250px;
        }

        .signature .name {
            margin-top: 70px;
            font-weight: bold;
            text-decoration: underline;
        }

        .btn-print {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 8px 16px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        @media print {
            .btn-print {
                display: none;
            }

            body {
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <button type="button" class="btn-print" onclick="window.print()">Cetak</button>

    <div class="header">
        <h2>{{ $company['nama_perusahaan'] }}</h2>
        <p>{{ $company['alamat'] }}</p>
        <p>{{ $company['telp'] }}</p>
    </div>

    <div class="title">NOTA BARANG MASUK</div>

    <div class="info">
        <table>
            <tr>
                <td>No. Nota</td>
                <td>:</td>
                <td>{{ $nota['no_nota'] }}</td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td>:</td>
                <td>{{ $nota['tanggal'] }}</td>
            </tr>
            <tr>
                <td>Supplier</td>
                <td>:</td>
                <td>{{ $nota['supplier'] }}</td>
            </tr>
            <tr>
                <td>Penerima</td>
                <td>:</td>
                <td>{{ $nota['nama_penerima'] }}</td>
            </tr>
        </table>
        <table>
            <tr>
                <td>Status Pembayaran</td>
                <td>:</td>
                <td>{{ $nota['status_pembayaran'] }}</td>
            </tr>
        </table>
    </div>

    <table class="detail">
        <thead>
            <tr>
                <th width="30">No</th>
                <th>Nama Barang</th>
                <th width="80">Jumlah</th>
                <th width="60">Satuan</th>
                <th width="110">Harga Beli</th>
                <th width="130">Sub Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($detailList as $index => $det)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $det->barang?->nama ?? 'Barang #' . $det->id_barang }}</td>
                    <td class="text-center">{{ number_format((float) $det->jumlah, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $det->barang?->satuan?->nama_satuan ?? '-' }}</td>
                    <td class="text-right">Rp {{ number_format((int) $det->harga_beli, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format((int) ($det->sub_total ?? $det->jumlah * $det->harga_beli), 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Tidak ada detail barang.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="text-right">TOTAL</td>
                <td class="text-right">Rp {{ number_format($nota['total_harga'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="terbilang">Terbilang : <strong>{{ ucfirst($nota['terbilang']) }} rupiah</strong></div>

    <table class="pembayaran">
        <thead>
            <tr>
                <th width="30">No</th>
                <th>Tanggal</th>
                <th>Jumlah Bayar</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pembayaranList as $index => $pm)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ \Carbon\Carbon::parse($pm->tanggal_pembayaran)->translatedFormat('d F Y') }}</td>
                    <td class="text-right">Rp {{ number_format((int) $pm->jumlah_bayar, 0, ',', '.') }}</td>
                    <td>{{ $pm->keterangan ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Belum ada pembayaran.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" class="text-right">TERBAYAR</td>
                <td class="text-right">Rp {{ number_format($nota['terbayar'], 0, ',', '.') }}</td>
                <td class="text-right">SISA : Rp {{ number_format($nota['sisa'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="signature">
        <div>
            <div>{{ $company['kota'] }}, {{ $nota['tanggal'] }}</div>
            <div class="name">{{ $company['nama_penandatangan'] }}</div>
            <div>{{ $company['jabatan_penandatangan'] }}</div>
        </div>
    </div>
</body>

</html>
