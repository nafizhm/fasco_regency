<a class="receivable-bell" href="{{ route('dashboard.piutang', ['jatuh_tempo' => 1]) }}"
   title="Piutang jatuh tempo hari ini: {{ $receivables['jatuh_tempo_hari_ini'] }} customer. Terlambat: {{ $receivables['terlambat'] }} customer."
   aria-label="{{ $receivables['notifikasi_piutang'] }} notifikasi piutang: {{ $receivables['jatuh_tempo_hari_ini'] }} customer jatuh tempo hari ini, {{ $receivables['terlambat'] }} customer terlambat. Buka rekap piutang.">
    <i class="fas fa-bell" aria-hidden="true"></i>
    <span class="receivable-bell-count" aria-hidden="true">{{ $receivables['notifikasi_piutang'] }}</span>
</a>
<style>
.receivable-bell { position:fixed; right:24px; bottom:24px; bottom:calc(24px + env(safe-area-inset-bottom, 0px)); width:58px; height:58px; border-radius:50%; background:#1769aa; color:#fff; display:flex; align-items:center; justify-content:center; font-size:25px; box-shadow:0 4px 16px rgba(0,0,0,.25); z-index:1030; text-decoration:none; }
.receivable-bell:hover, .receivable-bell:focus { background:#12558a; color:#fff; text-decoration:none; }
.receivable-bell:focus-visible { outline:3px solid #ffbf47; outline-offset:4px; }
.receivable-bell-count { position:absolute; top:-6px; right:-6px; min-width:25px; height:25px; padding:0 6px; display:flex; align-items:center; justify-content:center; border:2px solid #fff; border-radius:20px; background:#dc3545; color:#fff; font-size:12px; font-weight:700; }
@media print { .receivable-bell { display:none; } }
</style>
