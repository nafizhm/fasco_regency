@php
    $locations = \App\Models\PengajuanTagihan::LOKASI;
    $line = $line ?? ($data->exists && !request()->boolean('baru') ? $data->detail->first() : null);
    $values = old('detail.0', $line?->toArray() ?? []);
@endphp
<div class="modal-header">
    <h5 class="modal-title">{{ $data->exists ? ($line ? 'Edit' : 'Tambah Tagihan pada') : 'Tambah' }} Pengajuan Tagihan</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
</div>
<div class="modal-body">
    <div id="tagihanErrors" class="alert alert-danger" hidden role="alert"></div>
    <form id="tagihanForm" method="POST" enctype="multipart/form-data" action="{{ $data->exists ? route('pengajuan-tagihan.update', $data->id) : route('pengajuan-tagihan.store') }}">
        @csrf
        @if($data->exists) @method('PUT') @endif
        <input type="hidden" name="detail[0][id]" value="{{ $values['id'] ?? '' }}">
        <div class="form-group row"><label for="ptNomor" class="col-sm-4 col-form-label">Nomor pengajuan</label><div class="col-sm-8"><input id="ptNomor" name="nomor" class="form-control" maxlength="100" required value="{{ old('nomor', $data->nomor) }}"></div></div>
        <div class="form-group row"><label for="ptTanggal" class="col-sm-4 col-form-label">Tanggal pengajuan</label><div class="col-sm-8"><input id="ptTanggal" type="date" name="tanggal" class="form-control" required value="{{ old('tanggal', $data->tanggal) }}"></div></div>
        <div class="form-group row"><label for="ptStatus" class="col-sm-4 col-form-label">Status pengajuan</label><div class="col-sm-8"><select id="ptStatus" name="status" class="form-control">@foreach(\App\Models\PengajuanTagihan::STATUS as $status)<option @selected(old('status', $data->status) === $status)>{{ $status }}</option>@endforeach</select></div></div>
        <div class="form-group row"><label for="ptKeterangan" class="col-sm-4 col-form-label">Keterangan pengajuan</label><div class="col-sm-8"><textarea id="ptKeterangan" class="form-control" name="keterangan" maxlength="5000">{{ old('keterangan', $data->keterangan) }}</textarea></div></div>
        <hr>
        <div class="form-group row"><label for="ptNota" class="col-sm-4 col-form-label">Tanggal nota maksimal</label><div class="col-sm-8"><input id="ptNota" type="date" name="detail[0][tanggal_nota_max]" class="form-control" value="{{ $values['tanggal_nota_max'] ?? '' }}"></div></div>
        <div class="form-group row"><label for="ptNama" class="col-sm-4 col-form-label">Nama</label><div class="col-sm-8"><input id="ptNama" name="detail[0][nama]" class="form-control" required maxlength="255" value="{{ $values['nama'] ?? '' }}"></div></div>
        <div class="form-group row"><label for="ptUraian" class="col-sm-4 col-form-label">Uraian</label><div class="col-sm-8"><textarea id="ptUraian" name="detail[0][uraian]" class="form-control" rows="3" required maxlength="5000">{{ $values['uraian'] ?? '' }}</textarea></div></div>
        @foreach($locations as $key => $label)
        <div class="form-group row"><label for="pt_{{ $key }}" class="col-sm-4 col-form-label">{{ $label }} (Rp)</label><div class="col-sm-8"><input id="pt_{{ $key }}" name="detail[0][{{ $key }}]" class="form-control money text-right" inputmode="numeric" required value="{{ $values[$key] ?? 0 }}"></div></div>
        @endforeach
        <div class="form-group row"><label for="ptTotal" class="col-sm-4 col-form-label">Total (Rp)</label><div class="col-sm-8"><input id="ptTotal" class="form-control text-right font-weight-bold" readonly value="0"></div></div>
        <div class="form-group row"><label for="ptRekening" class="col-sm-4 col-form-label">Nomor rekening</label><div class="col-sm-8"><input id="ptRekening" name="detail[0][rekening]" class="form-control" maxlength="500" value="{{ $values['rekening'] ?? '' }}"></div></div>
        <div class="form-group row"><label for="ptLampiran" class="col-sm-4 col-form-label">Lampiran</label><div class="col-sm-8">
            @if($line?->lampiran)
            <p><a href="{{ route('pengajuan-tagihan.attachment', [$data->id, $line->id]) }}">{{ $line->nama_lampiran }}</a></p>
            <label><input type="checkbox" name="detail[0][hapus_lampiran]" value="1" @checked($values['hapus_lampiran'] ?? false)> Hapus lampiran lama</label>
            @endif
            <input id="ptLampiran" type="file" class="form-control-file" name="detail[0][file]" accept=".pdf,.jpg,.jpeg,.png,.webp,.xls,.xlsx,.doc,.docx">
            <small class="form-text text-muted">Maksimal 10 MB: PDF, gambar, Word atau Excel.</small>
        </div></div>
        <div class="row"><div class="col-sm-8 offset-sm-4"><button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button> <button class="btn btn-primary">Simpan</button></div></div>
    </form>
</div>
<script>
(() => {
 const form = document.getElementById('tagihanForm'), fmt = new Intl.NumberFormat('id-ID');
 const digits = value => String(value).replace(/[^0-9]/g, '');
 function total() {
  let sum = 0n;
  form.querySelectorAll('.money').forEach(input => sum += BigInt(digits(input.value) || '0'));
  document.getElementById('ptTotal').value = fmt.format(sum);
 }
 form.querySelectorAll('.money').forEach(input => {
  input.value = fmt.format(BigInt(digits(input.value) || '0'));
  input.addEventListener('input', () => { const n = digits(input.value); input.value = n ? fmt.format(BigInt(n)) : ''; total(); });
 });
 total();
document.getElementById('tagihanForm').addEventListener('submit', async event => {
 event.preventDefault();
 const form = event.currentTarget, errors = document.getElementById('tagihanErrors'), button = form.querySelector('button:not([type])');
 const payload = new FormData(form);
 form.querySelectorAll('.money').forEach(input => payload.set(input.name, digits(input.value)));
 errors.hidden = true; button.disabled = true;
 try {
  const response = await fetch(form.action, {method: 'POST', body: payload, headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
  const result = await response.json();
  if (!response.ok) {
   const messages = Object.values(result.errors || {}).flat();
   errors.textContent = messages.length ? messages.join(' ') : (result.message || 'Pengajuan gagal disimpan.');
   errors.hidden = false; errors.scrollIntoView({block: 'nearest'}); return;
  }
  window.location.assign(result.redirect);
 } catch (error) {
  errors.textContent = 'Pengajuan gagal disimpan. Periksa koneksi lalu coba kembali.'; errors.hidden = false;
 } finally { button.disabled = false; }
});
})();
</script>
