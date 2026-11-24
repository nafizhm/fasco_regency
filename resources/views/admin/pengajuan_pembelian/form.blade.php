@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper"><section class="content pt-3"><div class="container-fluid"><div class="card">
    <div class="card-header"><h3 class="card-title font-weight-bold">{{ $data->exists ? 'Edit' : 'Tambah' }} Purchase Order</h3></div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ $data->exists ? route('pengajuan-pembelian.update', $data->id) : route('pengajuan-pembelian.store') }}">
            @csrf @if($data->exists) @method('PUT') @endif
            <div class="form-group row"><label for="nomor" class="col-sm-2 col-form-label">Nomor Purchase Order</label><div class="col-sm-5"><input class="form-control" id="nomor" name="nomor" maxlength="100" required value="{{ old('nomor', $data->nomor) }}"></div></div>
            <div class="form-group row"><label for="tanggal" class="col-sm-2 col-form-label">Tanggal</label><div class="col-sm-3"><input type="date" class="form-control" id="tanggal" name="tanggal" required value="{{ old('tanggal', $data->tanggal) }}"></div></div>
            <div class="form-group row"><label for="id_supplier" class="col-sm-2 col-form-label">Supplier</label><div class="col-sm-5"><select class="form-control" id="id_supplier" name="id_supplier" required><option value="">Pilih Supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected(old('id_supplier', $data->id_supplier) == $supplier->id)>{{ $supplier->nama }}</option>@endforeach</select></div></div>
            <div class="form-group row"><label for="keterangan" class="col-sm-2 col-form-label">Keterangan</label><div class="col-sm-8"><textarea class="form-control" id="keterangan" name="keterangan" maxlength="5000">{{ old('keterangan', $data->keterangan) }}</textarea></div></div>
            <div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">Rencana Pembelian Barang</h5><button type="button" id="addItem" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Tambah Barang</button></div>
            <div class="table-responsive"><table class="table table-bordered" id="purchaseItems"><thead><tr><th style="min-width:220px">Barang</th><th>Satuan</th><th width="150">Jumlah Diajukan</th><th style="min-width:160px">Harga (Rp)</th><th style="min-width:180px">Subtotal (Rp)</th><th width="70">Action</th></tr></thead><tbody></tbody><tfoot><tr><th colspan="4" class="text-right">Total (Rp)</th><th id="purchaseTotal" class="text-right">0</th><th></th></tr></tfoot></table></div>
            <p class="text-muted">Harga pada purchase order merupakan harga rencana. Harga aktual diisi saat barang diterima.</p>
            <a href="{{ route('pengajuan-pembelian.index') }}" class="btn btn-secondary">Kembali</a>
            <button class="btn btn-primary" type="submit">Simpan</button>
        </form>
    </div>
</div></div></section></div>
<template id="purchaseItemTemplate"><tr>
    <td><select name="id_barang[]" class="form-control item-select" required><option value="">Pilih Barang</option>@foreach($barang as $item)<option value="{{ $item->id }}" data-satuan="{{ $item->satuan?->nama_satuan ?? '-' }}">{{ $item->nama }}</option>@endforeach</select></td>
    <td class="item-unit">-</td>
    <td><input name="jumlah[]" class="form-control quantity" inputmode="numeric" required></td>
    <td><input name="harga[]" class="form-control price text-right" inputmode="numeric" required value="0"></td>
    <td class="subtotal text-right">0</td>
    <td><button type="button" class="btn btn-danger btn-sm remove-item" title="Hapus Barang" aria-label="Hapus Barang"><i class="fas fa-trash"></i></button></td>
</tr></template>
@endsection
@push('scripts')
@php
    $ids = old('id_barang', $data->detail->pluck('id_barang')->all());
    $prices = old('harga', $data->detail->pluck('harga')->all());
    $quantities = old('jumlah', $data->detail->pluck('jumlah')->all());
@endphp
<script>
    const initialIds = @json($ids), initialQuantities = @json($quantities), initialPrices = @json($prices);
    const moneyFormat = new Intl.NumberFormat('id-ID');
    function calculatePurchase() {
        let total = 0n;
        $('#purchaseItems tbody tr').each(function() {
            const row = $(this);
            row.find('.item-unit').text(row.find('.item-select option:selected').attr('data-satuan') || '-');
            const digits = selector => row.find(selector).val().replace(/\D/g, '') || '0';
            const subtotal = BigInt(digits('.quantity')) * BigInt(digits('.price'));
            row.find('.subtotal').text(moneyFormat.format(subtotal));
            total += subtotal;
        });
        $('#purchaseTotal').text(moneyFormat.format(total));
    }
    function addPurchaseItem(id = '', quantity = '', price = '0') {
        const row = $(document.getElementById('purchaseItemTemplate').content.cloneNode(true));
        row.find('.item-select').val(String(id));
        row.find('.quantity').val(quantity);
        row.find('.price').val(moneyFormat.format(BigInt(String(price).replace(/\D/g, '') || '0')));
        $('#purchaseItems tbody').append(row);
        $('#purchaseItems tbody tr:last .item-select').select2({ theme: 'bootstrap4', width: '100%' });
        calculatePurchase();
    }
    initialIds.length ? initialIds.forEach((id, key) => addPurchaseItem(id, initialQuantities[key] || '', initialPrices[key] || '0')) : addPurchaseItem();
    $('#id_supplier').select2({ theme: 'bootstrap4', width: '100%' });
    $('#addItem').on('click', () => addPurchaseItem());
    $(document).on('click', '.remove-item', function() { if ($('#purchaseItems tbody tr').length > 1) $(this).closest('tr').remove(); calculatePurchase(); });
    $(document).on('input', '.quantity, .price', function() { const value = this.value.replace(/\D/g, ''); this.value = value ? new Intl.NumberFormat('id-ID').format(BigInt(value)) : ''; calculatePurchase(); });
    $(document).on('change', '.item-select', calculatePurchase);
</script>
@endpush
