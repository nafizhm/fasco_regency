<style>
    .receiving-attachments { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px; margin-bottom: 24px; }
    .receiving-attachments h5 { font-size: 16px; font-weight: 700; margin-bottom: 5px; }
    .receiving-attachment-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; height: 100%; }
    .receiving-attachment-icon { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 10px; background: #eff6ff; color: #2563eb; margin-bottom: 12px; }
    .receiving-attachment-card h6 { font-weight: 700; margin-bottom: 6px; }
    .receiving-file-list { list-style: none; padding: 0; margin: 12px 0 0; }
    .receiving-file { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-top: 1px solid #eef2f7; }
    .receiving-file img { width: 46px; height: 46px; object-fit: cover; border-radius: 6px; }
    .receiving-file-info { min-width: 0; flex: 1; }
    .receiving-file-info a { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 12px; }
    .receiving-file-info small { color: #64748b; }
    @media(max-width: 575px) { .receiving-attachments { padding: 16px; } }
</style>
<section class="receiving-attachments" aria-labelledby="receiving-attachments-title">
    <div class="d-flex align-items-center justify-content-between mb-1">
        <h5 id="receiving-attachments-title">Dokumen &amp; Foto Penerimaan</h5>
        <span class="badge badge-light border text-muted">Opsional</span>
    </div>
    <p class="text-muted small mb-3">Lengkapi bukti penerimaan bila tersedia. Barang masuk tetap bisa disimpan tanpa lampiran.</p>
    <div class="row">
        @foreach ([
            ['nota', 'Nota Pembelian', 'fa-file-invoice', 'Bukti pembelian dari supplier.', '.jpg,.jpeg,.png,.pdf', 'JPG, PNG, atau PDF'],
            ['surat_jalan', 'Surat Jalan', 'fa-truck', 'Dokumen pengiriman dari supplier.', '.jpg,.jpeg,.png,.pdf', 'JPG, PNG, atau PDF'],
            ['foto_barang', 'Foto Barang Datang', 'fa-camera', 'Foto kondisi dan jumlah barang yang diterima.', '.jpg,.jpeg,.png,.webp', 'JPG, PNG, atau WEBP'],
        ] as [$key, $title, $icon, $description, $accept, $formats])
            <div class="col-12 col-lg-4 mb-3 mb-lg-0">
                <div class="receiving-attachment-card" data-receiving-upload>
                    <span class="receiving-attachment-icon"><i class="fas {{ $icon }}" aria-hidden="true"></i></span>
                    <h6>{{ $title }}</h6>
                    <p class="text-muted small mb-3">{{ $description }}</p>
                    <label for="attachment-{{ $key }}" class="btn btn-outline-primary btn-sm mb-2"><i class="fas fa-plus mr-1" aria-hidden="true"></i> Pilih {{ $key === 'foto_barang' ? 'Foto' : 'File' }}</label>
                    <input type="file" id="attachment-{{ $key }}" name="lampiran_{{ $key }}[]" class="sr-only" accept="{{ $accept }}" multiple aria-label="{{ $title }}" aria-describedby="attachment-help-{{ $key }}">
                    <small id="attachment-help-{{ $key }}" class="d-block text-muted">{{ $formats }} · Maks. 2 MB/file · 10 file</small>
                    <p class="small text-muted mt-3 mb-0" data-upload-count aria-live="polite">Belum ada file dipilih</p>
                    <p class="small text-danger mb-0 mt-2" data-upload-error role="alert" hidden></p>
                    <ul class="receiving-file-list" data-upload-list></ul>
                </div>
            </div>
        @endforeach
    </div>
</section>
<script>
    document.querySelectorAll('[data-receiving-upload]').forEach(card => {
        const input = card.querySelector('input[type="file"]');
        const list = card.querySelector('[data-upload-list]');
        const error = card.querySelector('[data-upload-error]');
        const count = card.querySelector('[data-upload-count]');
        const extensions = input.accept.split(',');
        let files = [], urls = [];
        const sync = () => {
            const transfer = new DataTransfer();
            files.forEach(file => transfer.items.add(file));
            input.files = transfer.files;
            urls.forEach(url => URL.revokeObjectURL(url));
            urls = [];
            list.replaceChildren();
            count.textContent = files.length ? `${files.length} file siap diunggah` : 'Belum ada file dipilih';
            files.forEach((file, index) => {
                const row = document.createElement('li'); row.className = 'receiving-file';
                const url = URL.createObjectURL(file); urls.push(url);
                if (file.type.startsWith('image/')) {
                    const image = document.createElement('img'); image.src = url; image.alt = file.name; row.append(image);
                } else {
                    const icon = document.createElement('i'); icon.className = 'fas fa-file-pdf fa-2x text-danger'; icon.setAttribute('aria-hidden', 'true'); row.append(icon);
                }
                const info = document.createElement('div'); info.className = 'receiving-file-info';
                const link = document.createElement('a'); link.href = url; link.target = '_blank'; link.rel = 'noopener'; link.textContent = file.name; link.title = 'Lihat ' + file.name;
                const size = document.createElement('small'); size.textContent = (file.size / 1048576).toFixed(2) + ' MB';
                info.append(link, size);
                const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn btn-sm btn-light text-danger'; remove.textContent = '×'; remove.setAttribute('aria-label', 'Hapus ' + file.name);
                remove.addEventListener('click', () => { files.splice(index, 1); sync(); });
                row.append(info, remove); list.append(row);
            });
        };
        input.addEventListener('change', () => {
            const messages = [];
            Array.from(input.files).forEach(file => {
                const extension = '.' + file.name.split('.').pop().toLowerCase();
                if (!extensions.includes(extension)) { messages.push(file.name + ': format tidak didukung.'); return; }
                if (file.size > 2097152) { messages.push(file.name + ': melebihi 2 MB.'); return; }
                if (files.some(existing => existing.name === file.name && existing.size === file.size && existing.lastModified === file.lastModified)) return;
                if (files.length >= 10) { messages.push('Maksimal 10 file per kategori.'); return; }
                files.push(file);
            });
            error.textContent = messages.join(' '); error.hidden = !messages.length;
            sync();
        });
        window.addEventListener('pagehide', () => urls.forEach(url => URL.revokeObjectURL(url)));
    });
</script>
