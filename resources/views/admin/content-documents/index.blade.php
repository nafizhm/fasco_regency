@extends('admin.layout_admin')
@push('css')
<link rel="stylesheet" href="{{ asset('css/content-library.css') }}">
@endpush
@section('content')
@php
    $total = $categories->sum('total');
    $activeCategory = request('category');
@endphp
<div class="content-wrapper library-page">
    <main class="library-shell">
        <div class="library-breadcrumb">Workspace <span>/</span> <strong>Dokumentasi Konten</strong></div>
        <header class="library-header">
            <div><span class="library-eyebrow">YOUR CREATIVE SPACE</span><h1>Semua karya, satu tempat<span>.</span></h1><p>Simpan, temukan, dan bagikan dokumentasi terbaik tim Anda.</p></div>
            <button class="library-button library-button-primary" data-open-upload><i class="fas fa-plus" aria-hidden="true"></i> Upload konten</button>
        </header>
        <section class="library-stats" aria-label="Statistik konten">
            <div class="library-stat"><span class="library-stat-icon violet"><i class="far fa-folder-open" aria-hidden="true"></i></span><div><span>Total konten</span><strong>{{ number_format($total) }} <small>file tersimpan</small></strong></div><span class="library-stat-decoration">01</span></div>
            <div class="library-stat"><span class="library-stat-icon peach"><i class="fas fa-layer-group" aria-hidden="true"></i></span><div><span>Kategori</span><strong>{{ $categories->count() }} <small>koleksi konten</small></strong></div><span class="library-stat-decoration">02</span></div>
            <div class="library-stat"><span class="library-stat-icon mint"><i class="fas fa-filter" aria-hidden="true"></i></span><div><span>{{ $activeCategory ? 'Kategori terpilih' : 'Siap dijelajahi' }}</span><strong>{{ $documents->total() }} <small>{{ $activeCategory ?: 'seluruh konten' }}</small></strong></div><span class="library-stat-decoration">03</span></div>
        </section>
        <section class="library-collection" aria-labelledby="collection-title">
            <div class="library-toolbar"><div><h2 id="collection-title">Koleksi konten <span>{{ $documents->total() }}</span></h2><p>{{ $activeCategory ? 'Menampilkan kategori ' . $activeCategory : 'Semua dokumentasi, diurutkan dari yang terbaru.' }}</p></div>
                <form method="get" class="library-search" role="search">
                    @if($activeCategory)<input type="hidden" name="category" value="{{ $activeCategory }}">@endif
                    <i class="fas fa-search" aria-hidden="true"></i><input type="search" name="q" value="{{ request('q') }}" placeholder="Cari judul atau nama file..." aria-label="Cari judul atau nama file"><button type="submit" aria-label="Cari konten"><i class="fas fa-arrow-right" aria-hidden="true"></i></button>
                </form>
            </div>
            <nav class="library-categories" aria-label="Filter kategori">
                <a href="{{ route('content-documents.index', array_filter(['q' => request('q')])) }}" class="library-chip {{ !$activeCategory ? 'is-active' : '' }}" @if(!$activeCategory) aria-current="page" @endif><i class="fas fa-th-large" aria-hidden="true"></i> Semua konten <span>{{ $total }}</span></a>
                @foreach($categories as $category)
                    <a href="{{ route('content-documents.index', array_filter(['category' => $category->category, 'q' => request('q')])) }}" class="library-chip {{ $activeCategory === $category->category ? 'is-active' : '' }}" @if($activeCategory === $category->category) aria-current="page" @endif>{{ $category->category }} <span>{{ $category->total }}</span></a>
                @endforeach
            </nav>
            @if(request()->filled('q'))<div class="library-search-summary">Hasil pencarian ?{{ request('q') }}? <a href="{{ route('content-documents.index', array_filter(['category' => $activeCategory])) }}">Hapus pencarian <i class="fas fa-times" aria-hidden="true"></i></a></div>@endif
            <div class="library-grid">
                @foreach($documents as $document)
                    @php
                        $kind = str_starts_with($document->mime, 'image/') ? 'image' : (str_starts_with($document->mime, 'video/') ? 'video' : 'pdf');
                        $kindLabel = ['image' => 'Gambar', 'video' => 'Video', 'pdf' => 'PDF'][$kind];
                        $kindIcon = ['image' => 'fa-image', 'video' => 'fa-play', 'pdf' => 'fa-file-pdf'][$kind];
                        $fileUrl = route('content-documents.download', ['document' => $document->id, 'preview' => 1]);
                        $sizeLabel = $document->size >= 1073741824 ? number_format($document->size / 1073741824, 2) . ' GB' : number_format($document->size / 1048576, 2) . ' MB';
                    @endphp
                    <article class="library-item">
                        <button type="button" class="library-preview library-preview-{{ $kind }}" data-preview-url="{{ $fileUrl }}" data-preview-kind="{{ $kind }}" data-preview-title="{{ $document->title }}" data-download-url="{{ route('content-documents.download', $document->id) }}" aria-label="Lihat {{ $document->title }}">
                            @if($kind === 'image')<img src="{{ $fileUrl }}" alt="{{ $document->title }}" loading="lazy">@else<div class="library-file-art"><i class="fas {{ $kindIcon }}" aria-hidden="true"></i><span>{{ $kind === 'video' ? 'VIDEO CONTENT' : 'PDF DOCUMENT' }}</span></div>@endif
                            <span class="library-kind"><i class="fas {{ $kindIcon }}" aria-hidden="true"></i> {{ $kindLabel }}</span><span class="library-preview-action"><i class="fas fa-expand" aria-hidden="true"></i></span>
                        </button>
                        <div class="library-item-body"><span class="library-category-tag">{{ $document->category }}</span><h3 title="{{ $document->title }}">{{ $document->title }}</h3><p class="library-filename" title="{{ $document->original_name }}">{{ $document->original_name }}</p>
                            <div class="library-item-footer"><span>{{ $sizeLabel }} <b>?</b> {{ \Carbon\Carbon::parse($document->created_at)->translatedFormat('d M Y') }}</span><a href="{{ route('content-documents.download', $document->id) }}" class="library-download" aria-label="Download {{ $document->title }}" title="Download file"><i class="fas fa-arrow-down" aria-hidden="true"></i></a></div>
                        </div>
                    </article>
                @endforeach
            </div>
            @if($documents->isEmpty())
                <div class="library-empty"><div class="library-empty-art"><i class="far fa-folder-open" aria-hidden="true"></i><span><i class="fas fa-plus" aria-hidden="true"></i></span></div><h3>{{ request()->filled('q') || $activeCategory ? 'Belum menemukan yang dicari?' : 'Koleksi hebat dimulai dari satu file.' }}</h3><p>{{ request()->filled('q') || $activeCategory ? 'Coba kata kunci atau kategori lain untuk menemukan konten Anda.' : 'Upload foto, video, atau dokumen pertama Anda. Semua tersimpan rapi di sini.' }}</p>
                    @if(request()->filled('q') || $activeCategory)<a class="library-button library-button-light" href="{{ route('content-documents.index') }}">Tampilkan semua konten</a>@else<button class="library-button library-button-primary" data-open-upload><i class="fas fa-plus" aria-hidden="true"></i> Upload konten pertama</button>@endif
                    <div class="library-empty-formats"><span><i class="far fa-image" aria-hidden="true"></i> Gambar</span><span><i class="fas fa-video" aria-hidden="true"></i> Video</span><span><i class="far fa-file-pdf" aria-hidden="true"></i> PDF</span></div>
                </div>
            @endif
            <div class="library-pagination">{{ $documents->links('pagination::bootstrap-4') }}</div>
        </section>
        <div class="library-bottom-note"><i class="fas fa-shield-alt" aria-hidden="true"></i> Dokumentasi internal tim <span>?</span> File asli tersimpan tanpa kompresi</div>
    </main>
</div>
<dialog id="library-upload-dialog" class="library-dialog" aria-labelledby="upload-heading">
    <div class="library-dialog-heading"><div><span class="library-eyebrow">TAMBAHKAN KARYA BARU</span><h2 id="upload-heading">Upload konten</h2><p>Beri judul, pilih kategori, dan biarkan kami menyimpannya.</p></div><button type="button" class="library-close" data-close-dialog aria-label="Tutup form upload"><i class="fas fa-times" aria-hidden="true"></i></button></div>
    <div id="upload-error" class="library-error" role="alert" hidden></div>
    <form id="content-upload">
        @csrf
        <label class="library-dropzone" id="library-dropzone" for="content-file"><input id="content-file" type="file" accept="image/jpeg,image/png,image/gif,image/webp,image/avif,image/bmp,image/tiff,application/pdf,video/*" required><span class="library-drop-icon"><i class="fas fa-cloud-arrow-up" aria-hidden="true"></i></span><strong id="library-file-label">Tarik file ke sini, atau <em>pilih file</em></strong><span id="library-file-detail">Gambar, PDF, atau video ? Tanpa batas ukuran di form</span><span class="library-file-selected" id="library-file-selected" hidden><i class="fas fa-check-circle" aria-hidden="true"></i> File siap diupload ? Klik untuk mengganti</span></label>
        <div class="library-form-row"><div class="library-field"><label for="content-title">Judul konten <span>*</span></label><input id="content-title" name="title" required maxlength="255" placeholder="Contoh: Progres pembangunan Oktober"></div><div class="library-field"><label for="content-category">Kategori <span>*</span></label><input id="content-category" name="category" required maxlength="100" list="content-categories" placeholder="Pilih atau tulis kategori baru"><datalist id="content-categories">@foreach($categories as $category)<option value="{{ $category->category }}">@endforeach</datalist><small>Ketik nama baru untuk membuat kategori.</small></div></div>
        <div class="library-upload-note"><i class="fas fa-bolt" aria-hidden="true"></i><p><strong>File besar? Tetap tenang.</strong> Upload dikirim bertahap dengan percobaan ulang otomatis. Kapasitas penyimpanan server tetap berlaku.</p></div>
        <div class="library-dialog-footer"><button type="button" class="library-button library-button-light" data-close-dialog>Batal</button><button type="submit" class="library-button library-button-primary"><i class="fas fa-arrow-up" aria-hidden="true"></i> Upload sekarang</button></div>
    </form>
</dialog>
<dialog id="library-preview-dialog" class="library-dialog library-preview-dialog" aria-labelledby="preview-heading"><div class="library-preview-heading"><h2 id="preview-heading">Preview konten</h2><button class="library-close" data-close-dialog aria-label="Tutup preview"><i class="fas fa-times" aria-hidden="true"></i></button></div><div id="library-preview-stage"></div><div class="library-dialog-footer"><span>Preview file asli</span><a id="library-preview-download" class="library-button library-button-primary"><i class="fas fa-arrow-down" aria-hidden="true"></i> Download file</a></div></dialog>
<dialog id="upload-overlay" class="library-dialog library-progress-dialog" aria-labelledby="progress-heading"><div class="library-upload-animation"><i class="fas fa-cloud-arrow-up" aria-hidden="true"></i></div><span class="library-eyebrow">MENYIMPAN KARYA ANDA</span><h2 id="progress-heading">Sedang mengupload...</h2><p>Mohon tetap di halaman ini sampai selesai.</p><progress id="upload-progress" value="0" max="100" aria-label="Progres upload"></progress><div id="upload-status" aria-live="polite">Menyiapkan upload...</div><small><i class="fas fa-lock" aria-hidden="true"></i> File dikirim secara bertahap ke penyimpanan</small></dialog>
<dialog id="library-success" class="library-dialog library-progress-dialog" aria-labelledby="success-heading"><div class="library-success-icon"><i class="fas fa-check" aria-hidden="true"></i></div><h2 id="success-heading">Konten berhasil diupload!</h2><p>Karya Anda sudah tersimpan dan siap ditemukan di koleksi.</p><button id="library-success-done" class="library-button library-button-primary">Lihat koleksi <i class="fas fa-arrow-right" aria-hidden="true"></i></button></dialog>
@endsection
@push('scripts')
<script>
window.contentLibraryConfig = {beginUrl: @json(route('content-documents.begin')), uploadBase: @json(url('admin/dokumentasi-konten/uploads'))};
</script>
<script src="{{ asset('js/content-library.js') }}"></script>
@endpush
