<style>
  .work-heading { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; }
  .work-heading h2 { font-size:20px; font-weight:800; margin:0 0 6px; }
  .work-caption { color:var(--muted); font-size:13px; margin:0; }
  .work-add, .work-save { border:0; border-radius:10px; padding:11px 16px; background:var(--primary); color:#fff; font-weight:600; cursor:pointer; }
  .work-stats { display:flex; flex-wrap:wrap; gap:10px; margin:20px 0; }
  .work-pill { border-radius:8px; padding:7px 12px; font-size:13px; background:#f3f4f6; color:#4b5563; }
  .work-pill.active { background:#fff1d9; color:#925500; }
  .work-pill.done { background:#eaf8ef; color:#166534; }
  .work-list { display:grid; gap:10px; }
  .work-row { display:flex; align-items:center; gap:14px; padding:16px; border:1px solid var(--line); border-radius:12px; background:var(--surface); }
  .work-marker { flex-shrink:0; display:grid; place-items:center; width:36px; height:36px; border-radius:10px; background:#fff1d9; color:#a25e00; }
  .work-row.is-done .work-marker { background:#eaf8ef; color:#166534; }
  .work-main { flex:1; min-width:0; }
  .work-title { font-size:14px; font-weight:700; overflow-wrap:anywhere; }
  .work-notes { margin-top:5px; font-size:13px; color:var(--muted); white-space:pre-wrap; overflow-wrap:anywhere; }
  .work-high { display:inline-block; font-size:11px; color:#be123c; background:#fff1f2; padding:3px 8px; border-radius:6px; margin:0 0 6px; }
  .work-labels { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:8px; }
  .work-labels .work-pill { font-size:11px; padding:4px 9px; }
  .work-pill.high { color:#be123c; background:#fff1f2; }
  #work-entry .modal-content { border:0; border-radius:16px; overflow:hidden; }
  #work-entry .modal-title { font-size:18px; font-weight:700; }
  #work-entry .work-entry { margin:0; padding:20px; border:0; border-radius:0; }
  .work-status { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
  .work-status select { padding:8px 10px; border:1px solid var(--line); border-radius:8px; background:var(--surface); color:var(--text); font-size:13px; }
  .work-status button { border:1px solid var(--line); border-radius:8px; padding:8px 10px; color:var(--text); background:var(--surface); cursor:pointer; font-size:12px; }
  .work-entry { padding:18px; margin:18px 0; background:var(--bg); border:1px solid var(--line); border-radius:12px; }
  .work-entry label { display:block; font-size:13px; font-weight:600; margin-bottom:7px; }
  .work-entry input, .work-entry textarea, .work-entry select { width:100%; padding:10px 12px; border:1px solid var(--line); border-radius:8px; background:var(--surface); color:var(--text); }
  .work-form-grid { display:grid; grid-template-columns:1fr 180px; gap:14px; margin-bottom:14px; }
  .work-empty { text-align:center; padding:30px 16px; border:1px dashed var(--line); border-radius:12px; }
  @media(max-width:600px) { .work-row { align-items:flex-start; flex-wrap:wrap; } .work-status { width:100%; padding-left:50px; } .work-form-grid { grid-template-columns:1fr; } }
</style>
<section class="panel" id="work-priorities">
  <div class="panel-body">
    <div class="work-heading">
      <div><h2>Prioritas Pekerjaan</h2><p class="work-caption">Pantau fokus pekerjaan dan progres penyelesaiannya.</p></div>
      <button type="button" class="work-add" data-toggle="modal" data-target="#work-entry"><i class="fa-solid fa-plus" aria-hidden="true"></i> Tambah Pekerjaan</button>
    </div>
    @error('status')<div class="alert alert-danger mt-3" role="alert">{{ $message }}</div>@enderror
    <div class="work-stats">
      <span class="work-pill active"><strong>{{ $workPriorities->where('status', 'proses')->count() }}</strong> Proses</span>
      <span class="work-pill done"><strong>{{ $workPriorities->where('status', 'selesai')->count() }}</strong> Selesai</span>
      <span class="work-pill">{{ $workPriorities->count() }} total pekerjaan</span>
    </div>
    <div class="modal fade" id="work-entry" tabindex="-1" role="dialog" aria-labelledby="work-entry-title" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title" id="work-entry-title">Tambah Pekerjaan</h3>
            <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
          </div>
      <form class="work-entry" method="post" action="{{ route('work-priorities.store') }}">
        @csrf
        @if($errors->has('title') || $errors->has('notes') || $errors->has('priority'))
          <div class="alert alert-danger" role="alert">
            @foreach(['title', 'notes', 'priority'] as $field)
              @error($field)
                <div>{{ $message }}</div>
              @enderror
            @endforeach
          </div>
        @endif
        <div class="work-form-grid">
          <div><label for="work-title">Nama Pekerjaan</label><input id="work-title" name="title" value="{{ old('title') }}" placeholder="Contoh: Tindak lanjut pencairan KPR" maxlength="200" required></div>
          <div><label for="work-priority">Prioritas</label><select id="work-priority" name="priority"><option value="normal" @selected(old('priority') === 'normal')>Normal</option><option value="tinggi" @selected(old('priority') === 'tinggi')>Tinggi</option></select></div>
        </div>
        <label for="work-notes">Catatan <span class="work-caption">(opsional)</span></label>
        <textarea id="work-notes" name="notes" rows="2" maxlength="2000" placeholder="Tambahkan arahan atau hal yang perlu ditindaklanjuti">{{ old('notes') }}</textarea>
        <p class="work-caption mt-2">Pekerjaan baru berstatus Proses.</p>
        <div class="d-flex justify-content-end mt-3" style="gap:10px"><button type="button" class="btn btn-light" data-dismiss="modal">Batal</button><button class="work-save" type="submit">Simpan Pekerjaan</button></div>
      </form>
        </div>
      </div>
    </div>
    <div class="work-list">
      @forelse($workPriorities as $work)
        <article class="work-row {{ $work->status === 'selesai' ? 'is-done' : '' }}">
          <div class="work-marker"><i class="fa-solid {{ $work->status === 'selesai' ? 'fa-check' : 'fa-clock' }}" aria-hidden="true"></i></div>
          <div class="work-main">
            <div class="work-labels">
              <span class="work-pill {{ $work->priority === 'tinggi' ? 'high' : '' }}">Prioritas: <strong>{{ $work->priority === 'tinggi' ? 'Tinggi' : 'Normal' }}</strong></span>
              <span class="work-pill {{ $work->status === 'selesai' ? 'done' : 'active' }}">Progres: <strong>{{ $work->status === 'selesai' ? 'Selesai' : 'Proses' }}</strong></span>
            </div>
            <div class="work-title">{{ $work->title }}</div>
            @if($work->notes)<div class="work-notes">{{ $work->notes }}</div>@endif
          </div>
          <form class="work-status" method="post" action="{{ route('work-priorities.update', $work) }}">
            @csrf @method('PATCH')
            <label for="work-status-{{ $work->id }}" class="sr-only">Status {{ $work->title }}</label>
            <select id="work-status-{{ $work->id }}" name="status"><option value="proses" @selected($work->status === 'proses')>Proses</option><option value="selesai" @selected($work->status === 'selesai')>Selesai</option></select>
            <button type="submit" aria-label="Simpan status {{ $work->title }}">Simpan</button>
          </form>
        </article>
      @empty
        <div class="work-empty"><div class="work-title">Belum ada prioritas pekerjaan</div><p class="work-caption mt-2">Klik Tambah Pekerjaan untuk mulai menyusun fokus pekerjaan.</p></div>
      @endforelse
    </div>
  </div>
</section>
@push('scripts')
<script>
  $(function () {
    $('#work-entry').appendTo('body').on('shown.bs.modal', function () {
      $('#work-title').trigger('focus');
    });
    @if($errors->has('title') || $errors->has('notes') || $errors->has('priority'))
      $('#work-entry').modal('show');
    @endif
    @if(session('work_success'))
      Swal.fire({ icon: 'success', title: 'Berhasil', text: @json(session('work_success')), confirmButtonText: 'OK', confirmButtonColor: '#a25e00' });
    @endif
  });
</script>
@endpush
