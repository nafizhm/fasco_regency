<style>
.work-panels { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:20px; align-items:start; }
.work-panels .panel { min-width:0; }
.work-heading { display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; }
.work-heading h2 { font-size:19px; font-weight:800; margin:0 0 6px; }
.work-caption { color:var(--muted); font-size:12px; margin:0; }
.work-add, .work-save { border:0; border-radius:9px; padding:9px 12px; background:var(--primary); color:#fff; font-size:12px; font-weight:600; cursor:pointer; }
.work-stats { display:flex; flex-wrap:wrap; gap:8px; margin:18px 0; }
.work-pill { border-radius:8px; padding:6px 10px; font-size:12px; background:#f3f4f6; color:#4b5563; }
.work-pill.active { background:#fff1d9; color:#925500; }
.work-pill.done { background:#eaf8ef; color:#166534; }
.work-list { display:grid; gap:12px; max-height:580px; overflow-y:auto; padding-right:4px; }
.work-row { display:grid; grid-template-columns:30px minmax(0,1fr); align-items:start; gap:10px; padding:14px; border:1px solid var(--line); border-radius:12px; background:var(--surface); }
.work-marker { display:grid; place-items:center; width:30px; height:30px; border-radius:9px; background:#fff1d9; color:#a25e00; }
.work-row.is-done .work-marker { background:#eaf8ef; color:#166534; }
.work-main { min-width:0; }
.work-title { font-size:14px; line-height:1.5; font-weight:700; overflow-wrap:anywhere; }
.work-notes { margin-top:6px; font-size:12px; line-height:1.6; color:var(--muted); white-space:pre-wrap; overflow-wrap:anywhere; }
.work-assignee { margin-top:8px; font-size:12px; color:#1d4ed8; overflow-wrap:anywhere; }
.work-labels { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:8px; }
.work-labels .work-pill { font-size:10px; padding:4px 7px; }
.work-pill.high { color:#be123c; background:#fff1f2; }
.work-status { grid-column:2; display:flex; align-items:center; gap:8px; flex-wrap:wrap; padding-top:10px; border-top:1px solid var(--line); }
.work-status select { padding:6px 8px; border:1px solid var(--line); border-radius:8px; background:var(--surface); color:var(--text); font-size:12px; }
.work-status button { border:1px solid var(--line); border-radius:8px; padding:6px 10px; color:var(--text); background:var(--surface); cursor:pointer; font-size:12px; }
.work-entry-modal .modal-content { border:0; border-radius:16px; overflow:hidden; }
.work-entry-modal .modal-title { font-size:18px; font-weight:700; }
.work-entry { padding:20px; margin:0; background:var(--bg); }
.work-entry label { display:block; font-size:13px; font-weight:600; margin-bottom:7px; }
.work-entry input, .work-entry textarea, .work-entry select { width:100%; padding:10px 12px; border:1px solid var(--line); border-radius:8px; background:var(--surface); color:var(--text); }
.work-form-grid { display:grid; grid-template-columns:minmax(0,1fr) 150px; gap:14px; margin-bottom:14px; }
.work-empty { text-align:center; padding:30px 16px; border:1px dashed var(--line); border-radius:12px; }
@media(max-width:991px) { .work-panels { grid-template-columns:1fr; } }
@media(max-width:600px) { .work-form-grid { grid-template-columns:1fr; } .work-row { padding:12px; } }
</style>
<div class="work-panels">
@include('admin.beranda.work-panel', [
    'works'=>$workPriorities, 'delegated'=>false, 'panelId'=>'work-priorities',
    'modalId'=>'work-entry', 'bagName'=>'priorityWork', 'heading'=>'Prioritas Pekerjaan',
    'caption'=>'Pantau fokus pekerjaan dan progres penyelesaiannya.',
    'storeRoute'=>'work-priorities.store', 'updateRoute'=>'work-priorities.update',
])
@include('admin.beranda.work-panel', [
    'works'=>$workDelegations, 'delegated'=>true, 'panelId'=>'work-delegations',
    'modalId'=>'delegation-entry', 'bagName'=>'delegationWork', 'heading'=>'Delegasi Pekerjaan',
    'caption'=>'Pantau tugas karyawan dan progres penyelesaiannya.',
    'storeRoute'=>'work-delegations.store', 'updateRoute'=>'work-delegations.update',
])
</div>
@push('scripts')
<script>
$(function () {
    @if(session('work_success'))
        Swal.fire({ icon:'success', title:'Berhasil', text:@json(session('work_success')), confirmButtonText:'OK', confirmButtonColor:'#a25e00' });
    @endif
});
</script>
@endpush
