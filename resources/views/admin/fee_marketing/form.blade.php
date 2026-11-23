@extends('admin.layout_admin')
@section('content')
@php
$d = $record->data ?? [];
$value = fn ($field) => old($field, $d[$field] ?? '');
$rincian = old('rincian', $d['rincian'] ?? []);
$riwayat = old('riwayat', $d['riwayat'] ?? []);
@endphp
<div class="content-wrapper">
<section class="content-header"><h1>{{ $record->exists ? 'Edit' : 'Tambah' }} Fee Marketing</h1></section>
<section class="content"><form id="fee-form" method="POST" action="{{ $record->exists ? route('fee-marketing.update', $record) : route('fee-marketing.store') }}">
@csrf @if($record->exists) @method('PUT') @endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="card"><div class="card-header"><h3 class="card-title">Identitas Bukti Pembayaran</h3></div><div class="card-body">
<div class="row">
<div class="col-md-6 form-group"><label for="nomor">Nomor bukti</label><input required maxlength="150" id="nomor" name="nomor" class="form-control" value="{{ old('nomor', $record->nomor) }}" placeholder="001-P/I-24/FR/X/2026"></div>
<div class="col-md-6 form-group"><label for="tanggal">Tanggal pembayaran</label><input required type="date" id="tanggal" name="tanggal" class="form-control" value="{{ old('tanggal', $record->tanggal?->format('Y-m-d')) }}"></div>
@foreach(['diterima_dari'=>'Sudah Terima Dari', 'leader_tim'=>'Nama Leader / Tim', 'untuk_pembayaran'=>'Untuk Pembayaran', 'blok_skema'=>'Blok / Skema', 'nama_user'=>'Nama User', 'bank_rekening'=>'Bank & No rekening', 'atas_nama'=>'Atas Nama'] as $field=>$label)
<div class="col-md-6 form-group"><label for="{{ $field }}">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" class="form-control" maxlength="{{ in_array($field, ['bank_rekening','atas_nama']) ? 3000 : 500 }}" value="{{ $value($field) }}" @required(!in_array($field, ['bank_rekening','atas_nama']))></div>
@endforeach
</div></div></div>
<div class="card"><div class="card-header d-flex align-items-center"><h3 class="card-title">Rincian Fee</h3><button type="button" id="add-fee" class="btn btn-sm btn-outline-primary ml-auto">Tambah Rincian</button></div><div class="card-body">
<p class="text-muted small">Komisi = persentase × harga deal, dibulatkan ke rupiah. Total fee = BLT + komisi. Isi fee saat ini sesuai jumlah yang dibayarkan; dapat berupa pembayaran sebagian.</p>
<div class="table-responsive"><table class="table table-bordered table-sm fee-input-table"><thead><tr><th>Blok</th><th>Level</th><th>Harga Deal (Rp)</th><th>BLT (Rp)</th><th>%</th><th>Komisi (Rp)</th><th>Total Fee (Rp)</th><th>Fee Sebelumnya (Rp)</th><th>Fee Saat Ini (Rp)</th><th></th></tr></thead>
<tbody id="fee-rows">
@foreach($rincian as $i=>$row)
@include('admin.fee_marketing.fee-row', ['i'=>$i, 'row'=>$row])
@endforeach
</tbody></table></div>
<div class="text-right h5">Jumlah Pembayaran: <strong id="jumlah">Rp 0</strong></div>
<label for="catatan_harga">Catatan harga</label><input id="catatan_harga" name="catatan_harga" class="form-control" value="{{ $value('catatan_harga') }}" maxlength="3000">
</div></div>
<div class="card"><div class="card-header d-flex align-items-center"><h3 class="card-title">Penerimaan Fee Sebelumnya</h3><button type="button" id="add-history" class="btn btn-sm btn-outline-primary ml-auto">Tambah Riwayat</button></div><div class="card-body">
<div class="table-responsive"><table class="table table-bordered table-sm"><thead><tr><th>Tanggal</th><th>Fee Sebelumnya / Keterangan</th><th>Nominal (Rp)</th><th></th></tr></thead><tbody id="history-rows">
@foreach($riwayat as $i=>$row)
@include('admin.fee_marketing.history-row', ['i'=>$i, 'row'=>$row])
@endforeach
</tbody></table></div>
</div></div>
<div class="card"><div class="card-header"><h3 class="card-title">Kop, Ketentuan dan Tanda Tangan</h3></div><div class="card-body">
<div class="row">
@foreach(['perusahaan'=>'Nama Perusahaan', 'subjudul'=>'Subjudul Kop', 'alamat'=>'Alamat Perusahaan', 'telepon'=>'Telepon', 'proyek'=>'Nama Proyek', 'kota'=>'Kota', 'owner'=>'Nama Owner', 'jabatan_penerima'=>'Jabatan Penerima'] as $field=>$label)
<div class="col-md-6 form-group"><label for="{{ $field }}">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" class="form-control" value="{{ $value($field) }}" maxlength="{{ in_array($field,['subjudul','alamat','telepon']) ? 3000 : 500 }}" @required(!in_array($field,['subjudul','alamat','telepon']))></div>
@endforeach
</div>
<label for="ketentuan">Ketentuan (satu ketentuan per baris)</label><textarea id="ketentuan" name="ketentuan" rows="4" class="form-control" maxlength="3000">{{ $value('ketentuan') }}</textarea>
</div></div>
<div class="mb-4"><button class="btn btn-primary">Simpan Fee Marketing</button> <a class="btn btn-secondary" href="{{ route('fee-marketing.index') }}">Kembali</a></div>
</form></section></div>
<template id="fee-template">@include('admin.fee_marketing.fee-row', ['i'=>'__INDEX__', 'row'=>[]])</template>
<template id="history-template">@include('admin.fee_marketing.history-row', ['i'=>'__INDEX__', 'row'=>[]])</template>
<style>.fee-input-table input {min-width:115px}.fee-input-table input.percent {min-width:70px}.fee-input-table output {display:block;min-width:100px;text-align:right}</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('fee-form');
    const format = value => String(value).replace(/\D/g, '').replace(/^0+(?=\d)/, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const number = value => Number(String(value).replace(/\./g, '')) || 0;
    const money = value => value.toLocaleString('id-ID', {maximumFractionDigits:0});
    function update() {
        let jumlah = 0;
        document.querySelectorAll('#fee-rows tr').forEach(row => {
            const read = key => number(row.querySelector('[data-field="'+key+'"]').value);
            const persen = Number(row.querySelector('.percent').value) || 0;
            const komisi = Math.round(read('harga_deal') * Math.round(persen * 100) / 10000);
            row.querySelector('.komisi').textContent = money(komisi);
            row.querySelector('.total-fee').textContent = money(komisi + read('blt'));
            jumlah += read('fee_saat_ini');
        });
        document.getElementById('jumlah').textContent = 'Rp ' + money(jumlah);
    }
    form.querySelectorAll('.rupiah').forEach(input => input.value = format(input.value));
    form.addEventListener('input', event => {
        if (event.target.matches('.rupiah')) event.target.value = format(event.target.value);
        update();
    });
    let feeIndex = {{ count($rincian) ? max(array_keys($rincian)) + 1 : 0 }};
    let historyIndex = {{ count($riwayat) ? max(array_keys($riwayat)) + 1 : 0 }};
    document.getElementById('add-fee').addEventListener('click', () => {
        document.getElementById('fee-rows').insertAdjacentHTML('beforeend', document.getElementById('fee-template').innerHTML.replaceAll('__INDEX__', feeIndex++));
        update();
    });
    document.getElementById('add-history').addEventListener('click', () => {
        document.getElementById('history-rows').insertAdjacentHTML('beforeend', document.getElementById('history-template').innerHTML.replaceAll('__INDEX__', historyIndex++));
    });
    form.addEventListener('click', event => {
        const button = event.target.closest('.remove-row');
        if (button) { button.closest('tr').remove(); update(); }
    });
    form.addEventListener('submit', () => {
        form.querySelectorAll('.rupiah').forEach(input => input.value = input.value.replace(/\./g, ''));
    });
    update();
});
</script>
@endsection

