@extends('admin.layout_admin')
@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <h1>Piutang KPR</h1>
            <div>@if(strtolower((string) auth()->user()?->username) === 'tutut')<a href="{{ route('dashboard.piutang-kpr.pdf') }}" class="btn btn-primary btn-sm" target="_blank" rel="noopener"><i class="fas fa-print"></i> Cetak PDF</a>@endif <a href="{{ route('beranda.index') }}" class="btn btn-secondary btn-sm">Kembali ke Dashboard</a></div>
        </div>
    </section>
    <section class="content"><div class="container-fluid">
        <div class="card"><div class="card-body">
            <h3>LAPORAN PENCAIRAN TERMIN</h3>
            <p class="mb-1">Sisa piutang bank: <strong>Rp {{ number_format($rows->sum('sisa'), 0, ',', '.') }}</strong></p>
            <p class="text-muted small">Termin diurutkan berdasarkan tanggal pencairan. Persentase mengikuti nominal aktual terhadap plafon.
                Biaya KPR yang belum tercatat ditampilkan sebagai â€”. {{ $rows->whereNull('plafon')->count() }} customer belum memiliki plafon; belum masuk total piutang bank.</p>
            <div class="table-responsive">
                @include('admin.dashboard.piutang-kpr-table')
            </div>
        </div></div>
    </div></section>
</div>
<style>.termin-report{font-size:13px;white-space:nowrap}.termin-report thead th{background:#e7effa;text-align:center;vertical-align:middle}.termin-report tfoot{background:#f2f5f9}</style>
@endsection
