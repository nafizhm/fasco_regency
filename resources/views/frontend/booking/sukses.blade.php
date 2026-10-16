@extends('layouts.app')

@section('title', 'Booking Berhasil')

@php
    $profil = \App\Models\PengaturanProfil::first();
    $logo = \App\Models\PengaturanMedia::where('jenis_data', 'Logo Login')->where('stt_aktif', 1)->first();
@endphp

@push('styles')
<style>
    body { background: #eff4fa !important; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
    .booking-success { min-height: 100vh; min-height: 100svh; padding: 36px 20px; display: flex; align-items: center; justify-content: center; background: radial-gradient(ellipse at top left, #d9eaff, transparent 60%), linear-gradient(150deg, #f6f9ff, #e9f2ee); }
    @if ($bg?->nama_file)
        .booking-success { background: linear-gradient(135deg, rgba(239,246,255,.92), rgba(236,253,245,.92)), url('{{ asset('config_media/' . $bg->nama_file) }}') center / cover; }
    @endif
    .success-wrap { width: 100%; max-width: 620px; }
    .success-brand { display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 22px; color: #24405a; font-size: 14px; font-weight: 600; }
    .success-brand img { max-width: 64px; max-height: 56px; object-fit: contain; }
    .success-card { border-radius: 26px; background: #fff; overflow: hidden; box-shadow: 0 20px 65px #143d6620; border: 1px solid #fff; }
    .success-hero { position: relative; text-align: center; padding: 32px 28px 26px; background: linear-gradient(135deg, #0d3b66, #1e5fa8); color: #fff; }
    .success-check { display: flex; align-items: center; justify-content: center; width: 64px; height: 64px; margin: 0 auto 16px; border-radius: 50%; background: #d1fae5; color: #047857; box-shadow: 0 0 0 8px #ffffff12; }
    .success-hero .eyebrow { color: #b5dccf; text-transform: uppercase; font-size: 11px; letter-spacing: 1.8px; font-weight: 700; margin-bottom: 7px; }
    .success-hero h1 { font-size: clamp(26px, 6vw, 34px); font-weight: 700; margin: 0 0 10px; letter-spacing: -.6px; }
    .success-hero p { color: #dfebf7; font-size: 14px; line-height: 1.6; margin: 0; }
    .success-body { padding: 26px 28px; }
    .success-reference { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding-bottom: 18px; border-bottom: 1px dashed #dbe3ed; font-size: 12px; color: #64748b; }
    .success-reference strong { color: #1e3a5f; overflow-wrap: anywhere; }
    .success-summary { margin: 18px 0 0; }
    .success-summary dt { color: #64748b; font-size: 12px; font-weight: 400; margin-bottom: 5px; }
    .success-summary dd { color: #132b43; font-size: 15px; font-weight: 600; margin-bottom: 18px; overflow-wrap: anywhere; }
    .success-unit { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .success-unit > div { background: #f3f7fb; border: 1px solid #e5edf5; border-radius: 12px; padding: 12px 14px; min-width: 0; }
    .success-unit dd { margin-bottom: 0; }
    .success-next { display: flex; gap: 12px; background: #ecfdf5; border: 1px solid #d1fae5; border-radius: 14px; padding: 15px; margin: 22px 0; color: #14533e; }
    .success-next svg { flex-shrink: 0; margin-top: 2px; }
    .success-next strong { font-size: 13px; display: block; margin-bottom: 4px; }
    .success-next p { font-size: 12px; line-height: 1.65; margin: 0; }
    .success-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .success-action { min-height: 48px; display: flex; align-items: center; justify-content: center; gap: 9px; padding: 12px; border-radius: 12px; text-decoration: none; font-size: 14px; font-weight: 600; border: 1px solid #d9e3ef; color: #24435f; background: #fff; }
    .success-action.primary { background: #1e5fa8; color: #fff; border-color: #1e5fa8; }
    .success-action:hover { text-decoration: none; background: #f0f5fb; color: #193e60; }
    .success-action.primary:hover { background: #164a85; color: #fff; }
    .success-action:focus-visible { outline: 3px solid #60a5fa; outline-offset: 3px; }
    .success-footer { text-align: center; color: #64748b; font-size: 12px; margin: 18px 0 0; }
    @media (max-width: 480px) {
        .booking-success { padding: 22px 14px; align-items: flex-start; }
        .success-brand { margin-bottom: 16px; font-size: 12px; }
        .success-brand img { max-height: 44px; max-width: 52px; }
        .success-card { border-radius: 20px; }
        .success-hero { padding: 26px 20px 23px; }
        .success-body { padding: 22px 18px; }
        .success-actions { grid-template-columns: 1fr; gap: 9px; }
        .success-unit > div { padding: 12px; }
        .success-reference { align-items: flex-start; }
    }
</style>
@endpush

@section('content')
<main class="booking-success">
    <div class="success-wrap">
        <div class="success-brand">
            @if ($logo?->nama_file)<img src="{{ asset('config_media/' . $logo->nama_file) }}" alt="Logo {{ $profil?->nama_perusahaan }}">@endif
            <span>{{ $profil?->nama_perusahaan ?: 'Booking Rumah' }}</span>
        </div>
        <section class="success-card" aria-labelledby="success-title">
            <div class="success-hero">
                <div class="success-check" aria-hidden="true"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4L19 6"/></svg></div>
                <div class="eyebrow">Permohonan terkirim</div>
                <h1 id="success-title">Booking berhasil!</h1>
                <p>Terima kasih. Satu langkah lebih dekat<br>menuju rumah pilihan Anda.</p>
            </div>
            <div class="success-body">
                @if (session('nama'))
                    @if (session('no_registrasi'))
                        <div class="success-reference"><span>Nomor registrasi</span><strong>{{ session('no_registrasi') }}</strong></div>
                    @endif
                    <dl class="success-summary">
                        <dt>Nama pemesan</dt><dd>{{ session('nama') }}</dd>
                        <dt>Perumahan</dt><dd>{{ session('lokasi') }}</dd>
                        <div class="success-unit">
                            <div><dt>Cluster</dt><dd>{{ session('cluster', '-') }}</dd></div>
                            <div><dt>Kavling</dt><dd>{{ session('blok', '-') }}</dd></div>
                        </div>
                    </dl>
                @endif
                <div class="success-next">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    <div><strong>Selanjutnya, tunggu konfirmasi tim kami</strong><p>Tim kami akan memeriksa data booking dan menghubungi Anda melalui nomor telepon atau WhatsApp yang dicantumkan.</p></div>
                </div>
                <div class="success-actions">
                    <a class="success-action primary" href="{{ route('public.siteplan.index') }}"><i class="fas fa-map-marked-alt" aria-hidden="true"></i> Lihat siteplan</a>
                    <a class="success-action" href="{{ route('booking') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Kembali ke booking</a>
                </div>
            </div>
        </section>
        <p class="success-footer">{{ session('no_registrasi') ? 'Simpan nomor registrasi untuk konfirmasi booking Anda.' : 'Terima kasih atas kepercayaan Anda.' }}</p>
    </div>
</main>
@endsection
