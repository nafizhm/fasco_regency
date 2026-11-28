<table class="identity table table-bordered"><tr><th>Kepada Yth Bpk / Ibu</th><td>{{ $data->nama }}</td></tr><tr><th>Alamat Rumah</th><td>{{ $data->alamat }}</td></tr></table>
<table class="table table-bordered period"><thead><tr><th>Tanggal STU</th><th>Nominal / tahun</th><th>Jatuh tempo</th></tr></thead><tbody><tr><td>{{ \Carbon\Carbon::parse($data->tanggal_stu)->translatedFormat('d F Y') }}</td><td>Rp{{ number_format($data->nominal_tahun,0,',','.') }}</td><td>{{ \Carbon\Carbon::parse($data->jatuh_tempo)->translatedFormat('d F Y') }}</td></tr></tbody></table>
<h4>PEMBAYARAN:</h4>
<table class="table table-bordered payments"><thead><tr><th>Tanggal Pembayaran</th><th>Nominal</th><th>Transaksi</th><th>Keterangan</th></tr></thead><tbody>
@forelse($data->pembayaran as $line)<tr><td>{{ \Carbon\Carbon::parse($line->tanggal)->translatedFormat('d F Y') }}</td><td class="money">Rp{{ number_format($line->nominal,0,',','.') }}</td><td>{{ $line->transaksi }}</td><td>{{ $line->keterangan }}</td></tr>@empty<tr><td colspan="4">Belum ada pembayaran.</td></tr>@endforelse
<tr class="total"><th>TOTAL PEMBAYARAN</th><th class="money">Rp{{ number_format($data->total_pembayaran,0,',','.') }}</th><td colspan="2"></td></tr><tr><th>SISA TAGIHAN</th><th class="money">Rp{{ number_format($data->sisa,0,',','.') }}</th><td colspan="2">{{ $data->status }}</td></tr></tbody></table>
<table class="table table-bordered bank" style="table-layout:fixed">
    <colgroup><col style="width:26%"><col style="width:85px"><col style="width:10px"><col></colgroup>
    @foreach(['nama_rekening' => 'Nama', 'bank' => 'Bank', 'no_rekening' => 'No Rekening'] as $field => $label)
    <tr>
        @if($loop->first)<th rowspan="3" style="vertical-align:middle;text-align:center">PEMBAYARAN</th>@endif
        <td style="border-right:0;white-space:nowrap;padding-right:2px">{{ $label }}</td>
        <td style="border-left:0;border-right:0;text-align:center;padding-left:0;padding-right:0">:</td>
        <td style="border-left:0;padding-left:2px">{{ \App\Models\InvoiceIpl::PERUSAHAAN[$field] }}</td>
    </tr>
    @endforeach
</table>
<div class="signature"><p>Hormat Kami,</p><div style="height:65px"></div><u>{{ \App\Models\InvoiceIpl::PERUSAHAAN['penandatangan'] }}</u><br>{{ \App\Models\InvoiceIpl::PERUSAHAAN['jabatan'] }}</div>
