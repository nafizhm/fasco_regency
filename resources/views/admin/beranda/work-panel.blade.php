@php
$bag = $errors->getBag($bagName);
$restore = old('_work_panel') === $panelId;
$fieldValue = fn($field, $default = '') => $restore ? old($field, $default) : $default;
@endphp
<section class="panel" id="{{ $panelId }}" aria-labelledby="{{ $panelId }}-heading">
<div class="panel-body">
<div class="work-heading">
    <div><h2 id="{{ $panelId }}-heading">{{ $heading }}</h2><p class="work-caption">{{ $caption }}</p></div>
    <button type="button" class="work-add" data-toggle="modal" data-target="#{{ $modalId }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> {{ $delegated ? 'Tambah Delegasi' : 'Tambah Pekerjaan' }}</button>
</div>
@if($bag->has('status'))<div class="alert alert-danger mt-3" role="alert">{{ $bag->first('status') }}</div>@endif
<div class="work-stats">
    <span class="work-pill active"><strong>{{ $works->where('status','proses')->count() }}</strong> Proses</span>
    <span class="work-pill done"><strong>{{ $works->where('status','selesai')->count() }}</strong> Selesai</span>
    <span class="work-pill">{{ $works->count() }} total pekerjaan</span>
</div>
<div class="modal fade work-entry-modal" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-labelledby="{{ $modalId }}-title" aria-hidden="true">
<div class="modal-dialog modal-lg modal-dialog-centered" role="document"><div class="modal-content">
<div class="modal-header">
    <h3 class="modal-title" id="{{ $modalId }}-title">{{ $delegated ? 'Tambah Delegasi Pekerjaan' : 'Tambah Pekerjaan' }}</h3>
    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
</div>
<form class="work-entry" method="post" action="{{ route($storeRoute) }}">
    @csrf
    <input type="hidden" name="_work_panel" value="{{ $panelId }}">
    @if($bag->any() && !$bag->has('status'))
    <div class="alert alert-danger" role="alert">@foreach($bag->all() as $message)<div>{{ $message }}</div>@endforeach</div>
    @endif
    <div class="work-form-grid">
        <div><label for="{{ $modalId }}-name">Nama Pekerjaan</label><input id="{{ $modalId }}-name" name="title" value="{{ $fieldValue('title') }}" placeholder="Contoh: Tindak lanjut pencairan KPR" maxlength="200" required></div>
        <div><label for="{{ $modalId }}-priority">Prioritas</label><select id="{{ $modalId }}-priority" name="priority"><option value="normal" @selected($fieldValue('priority','normal') === 'normal')>Normal</option><option value="tinggi" @selected($fieldValue('priority') === 'tinggi')>Tinggi</option></select></div>
    </div>
    @if($delegated)
    <div class="mb-3"><label for="{{ $modalId }}-employee">Nama Karyawan yang Didelegasikan</label><input id="{{ $modalId }}-employee" name="delegated_to" value="{{ $fieldValue('delegated_to') }}" placeholder="Nama karyawan penerima tugas" maxlength="200" required></div>
    @endif
    <label for="{{ $modalId }}-notes">Catatan <span class="work-caption">(opsional)</span></label>
    <textarea id="{{ $modalId }}-notes" name="notes" rows="3" maxlength="2000" placeholder="Tambahkan arahan atau hal yang perlu ditindaklanjuti">{{ $fieldValue('notes') }}</textarea>
    <p class="work-caption mt-2">Pekerjaan baru berstatus Proses.</p>
    <div class="d-flex justify-content-end mt-3" style="gap:10px"><button type="button" class="btn btn-light" data-dismiss="modal">Batal</button><button class="work-save" type="submit">{{ $delegated ? 'Simpan Delegasi' : 'Simpan Pekerjaan' }}</button></div>
</form>
</div></div></div>
<div class="work-list" tabindex="0" aria-label="Daftar {{ $heading }}">
@forelse($works as $work)
<article class="work-row {{ $work->status === 'selesai' ? 'is-done' : '' }}">
    <div class="work-marker"><i class="fa-solid {{ $work->status === 'selesai' ? 'fa-check' : 'fa-clock' }}" aria-hidden="true"></i></div>
    <div class="work-main">
        <div class="work-labels">
            <span class="work-pill {{ $work->priority === 'tinggi' ? 'high' : '' }}">Prioritas: <strong>{{ $work->priority === 'tinggi' ? 'Tinggi' : 'Normal' }}</strong></span>
            <span class="work-pill {{ $work->status === 'selesai' ? 'done' : 'active' }}">Progres: <strong>{{ $work->status === 'selesai' ? 'Selesai' : 'Proses' }}</strong></span>
        </div>
        <div class="work-title">{{ $work->title }}</div>
        @if($delegated)<div class="work-assignee"><i class="fa-solid fa-user" aria-hidden="true"></i> Ditugaskan kepada: <strong>{{ $work->delegated_to }}</strong></div>@endif
        @if($work->notes)<div class="work-notes">{{ $work->notes }}</div>@endif
    </div>
    <form class="work-status" method="post" action="{{ route($updateRoute,$work) }}">
        @csrf @method('PATCH')
        <label for="{{ $panelId }}-status-{{ $work->id }}" class="sr-only">Status {{ $work->title }}</label>
        <select id="{{ $panelId }}-status-{{ $work->id }}" name="status"><option value="proses" @selected($work->status === 'proses')>Proses</option><option value="selesai" @selected($work->status === 'selesai')>Selesai</option></select>
        <button type="submit" aria-label="Simpan status {{ $work->title }}">Simpan Status</button>
    </form>
</article>
@empty
<div class="work-empty"><div class="work-title">{{ $delegated ? 'Belum ada delegasi pekerjaan' : 'Belum ada prioritas pekerjaan' }}</div><p class="work-caption mt-2">{{ $delegated ? 'Tambahkan tugas dan nama karyawan penerimanya.' : 'Klik Tambah Pekerjaan untuk mulai menyusun fokus pekerjaan.' }}</p></div>
@endforelse
</div>
</div></section>
@push('scripts')
<script>
$(function () {
    $('#{{ $modalId }}').appendTo('body').on('shown.bs.modal', function () {
        $('#{{ $modalId }}-name').trigger('focus');
    });
    @if($bag->any() && !$bag->has('status'))
        $('#{{ $modalId }}').modal('show');
    @endif
});
</script>
@endpush

