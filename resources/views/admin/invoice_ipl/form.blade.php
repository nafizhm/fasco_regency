@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper"><section class="content-header"><h1>{{ $data->exists ? 'Edit' : 'Tambah' }} Invoice IPL</h1></section><section class="content"><div class="card"><div class="card-body">
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form id="iplForm" method="POST" action="{{ $data->exists ? route('invoice-ipl.update',$data->id) : route('invoice-ipl.store') }}">
@csrf @if($data->exists) @method('PUT') @endif
<h5>Identitas Invoice</h5>
@foreach(['nomor'=>'Nomor invoice','nama'=>'Kepada Yth Bpk / Ibu','alamat'=>'Alamat rumah','tanggal_stu'=>'Tanggal STU','nominal_tahun'=>'Nominal / tahun (Rp)','jatuh_tempo'=>'Jatuh tempo'] as $field=>$label)
<div class="form-group row"><label for="ipl_{{ $field }}" class="col-sm-3 col-form-label">{{ $label }}</label><div class="col-sm-9">
@if($field==='alamat')<textarea id="ipl_{{ $field }}" class="form-control" name="{{ $field }}" required maxlength="3000">{{ old($field,$data->$field) }}</textarea>
@else<input id="ipl_{{ $field }}" name="{{ $field }}" class="form-control {{ $field==='nominal_tahun' ? 'money text-right' : '' }}" type="{{ in_array($field,['tanggal_stu','jatuh_tempo']) ? 'date' : 'text' }}" @if($field==='nominal_tahun') inputmode="numeric" @else maxlength="{{ $field==='nomor' ? 100 : 255 }}" @endif required value="{{ old($field,$data->$field) }}">@endif
</div></div>@endforeach
<hr><div class="d-flex justify-content-between mb-2"><h5>Pembayaran</h5><button type="button" id="addPayment" class="btn btn-primary btn-sm">Tambah Pembayaran</button></div>
<p class="text-muted">Pembayaran dapat dikosongkan untuk invoice yang belum dibayar.</p>
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Tanggal pembayaran</th><th>Nominal (Rp)</th><th>Transaksi</th><th>Keterangan</th><th>Aksi</th></tr></thead><tbody id="paymentRows"></tbody><tfoot><tr><th>Total pembayaran (Rp)</th><th id="paidTotal" class="text-right">0</th><th colspan="3">Sisa tagihan (Rp): <span id="balance">0</span></th></tr></tfoot></table></div>
<a class="btn btn-secondary" href="{{ route('invoice-ipl.index') }}">Kembali</a> <button type="submit" class="btn btn-primary">Simpan</button>
</form></div></div></section></div>
<template id="paymentTemplate"><tr><td><input type="date" class="form-control" data-field="tanggal" required style="min-width:150px"></td><td><input class="form-control money text-right" data-field="nominal" inputmode="numeric" required value="0" style="min-width:150px"></td><td><select class="form-control" data-field="transaksi">@foreach(['Transfer','Tunai','QRIS','Debit','Lainnya'] as $method)<option>{{ $method }}</option>@endforeach</select></td><td><input class="form-control" data-field="keterangan" maxlength="1000" style="min-width:200px"></td><td><button type="button" class="btn btn-danger btn-sm remove-payment">Hapus</button></td></tr></template>
@endsection
@push('scripts')
<script>
(() => {
 const form=document.getElementById('iplForm'), body=document.getElementById('paymentRows'), fmt=new Intl.NumberFormat('id-ID');
 const digits=value=>String(value).replace(/[^0-9]/g,''), number=value=>BigInt(digits(value)||'0');
 let index=0;
 function totals() {
  let sum=0n; body.querySelectorAll('.money').forEach(input=>sum+=number(input.value));
  const remaining=number(document.getElementById('ipl_nominal_tahun').value)-sum;
  document.getElementById('paidTotal').textContent=fmt.format(sum);
  document.getElementById('balance').textContent=fmt.format(remaining>0n?remaining:0n);
 }
 function format(input) { const value=digits(input.value); input.value=value?fmt.format(BigInt(value)):''; }
 function add(line={}) {
  const row=document.getElementById('paymentTemplate').content.cloneNode(true), key=index++;
  row.querySelectorAll('[data-field]').forEach(input=>{ input.name=`pembayaran[${key}][${input.dataset.field}]`; input.value=line[input.dataset.field]??(input.dataset.field==='transaksi'?'Transfer':input.dataset.field==='nominal'?'0':''); if(input.classList.contains('money')) format(input); });
  body.append(row); totals();
 }
 const initial=@json(session()->hasOldInput() ? old('pembayaran', []) : $data->pembayaran->toArray());
 Object.values(initial||[]).forEach(add);
 form.querySelectorAll('.money').forEach(format); totals();
 document.getElementById('addPayment').onclick=()=>{if(body.rows.length<100)add();};
 form.addEventListener('input',event=>{if(event.target.classList.contains('money')){format(event.target);totals();}});
 body.addEventListener('click',event=>{if(event.target.classList.contains('remove-payment')){event.target.closest('tr').remove();totals();}});
 form.addEventListener('submit',()=>form.querySelectorAll('.money').forEach(input=>input.value=digits(input.value)));
})();
</script>
@endpush
