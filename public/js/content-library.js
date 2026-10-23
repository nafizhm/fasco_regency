(() => {
    const form = document.getElementById('content-upload');
    const uploadDialog = document.getElementById('library-upload-dialog');
    const overlay = document.getElementById('upload-overlay');
    const success = document.getElementById('library-success');
    const previewDialog = document.getElementById('library-preview-dialog');
    const stage = document.getElementById('library-preview-stage');
    const status = document.getElementById('upload-status');
    const progress = document.getElementById('upload-progress');
    const fileInput = document.getElementById('content-file');
    const dropzone = document.getElementById('library-dropzone');
    let uploading = false;
    document.querySelectorAll('[data-open-upload]').forEach(button => button.addEventListener('click', () => uploadDialog.showModal()));
    document.querySelectorAll('[data-close-dialog]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
    [uploadDialog, previewDialog].forEach(dialog => dialog.addEventListener('click', event => {
        if (event.target !== dialog) return;
        const bounds = dialog.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
    }));
    previewDialog.addEventListener('close', () => stage.replaceChildren());
    document.querySelectorAll('[data-preview-url]').forEach(button => button.addEventListener('click', () => {
        stage.replaceChildren();
        document.getElementById('preview-heading').textContent = button.dataset.previewTitle;
        document.getElementById('library-preview-download').href = button.dataset.downloadUrl;
        const kind = button.dataset.previewKind;
        const media = document.createElement(kind === 'image' ? 'img' : kind === 'video' ? 'video' : 'iframe');
        media.src = button.dataset.previewUrl;
        if (kind === 'image') media.alt = button.dataset.previewTitle;
        if (kind === 'video') { media.controls = true; media.preload = 'metadata'; }
        if (kind === 'pdf') media.title = button.dataset.previewTitle;
        stage.append(media);
        previewDialog.showModal();
    }));
    const formatSize = size => size >= 1073741824 ? (size / 1073741824).toFixed(2) + ' GB' : (size / 1048576).toFixed(2) + ' MB';
    function showSelectedFile() {
        const file = fileInput.files[0];
        if (!file) return;
        document.getElementById('library-file-label').textContent = file.name;
        document.getElementById('library-file-detail').textContent = formatSize(file.size) + ' ? ' + (file.type || 'File terpilih');
        document.getElementById('library-file-selected').hidden = false;
        if (!form.elements.title.value.trim()) form.elements.title.value = file.name.replace(/\.[^.]+$/, '').slice(0, 255);
    }
    fileInput.addEventListener('change', showSelectedFile);
    ['dragenter', 'dragover'].forEach(name => dropzone.addEventListener(name, event => { event.preventDefault(); dropzone.classList.add('is-dragover'); }));
    ['dragleave', 'drop'].forEach(name => dropzone.addEventListener(name, event => { event.preventDefault(); dropzone.classList.remove('is-dragover'); }));
    dropzone.addEventListener('drop', event => {
        if (!event.dataTransfer.files.length) return;
        const transfer = new DataTransfer();
        transfer.items.add(event.dataTransfer.files[0]);
        fileInput.files = transfer.files;
        showSelectedFile();
    });
    window.addEventListener('beforeunload', event => { if (uploading) { event.preventDefault(); event.returnValue = ''; } });
    overlay.addEventListener('cancel', event => event.preventDefault());
    success.addEventListener('cancel', event => { event.preventDefault(); window.location.reload(); });
    document.getElementById('library-success-done').addEventListener('click', () => window.location.reload());
    async function send(url, body) {
        for (let attempt = 0; attempt < 3; attempt++) {
            try {
                const response = await fetch(url, {method: 'POST', body, headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value}});
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : '';
                    const error = new Error(errors || data.message || `Upload gagal (HTTP ${response.status}). Silakan coba lagi.`);
                    error.noRetry = response.status < 500;
                    throw error;
                }
                return data;
            } catch (error) {
                if (error.noRetry || attempt === 2) throw error;
                status.textContent = 'Koneksi terganggu, mencoba kembali...';
                await new Promise(resolve => setTimeout(resolve, 1500 * (attempt + 1)));
            }
        }
    }
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (uploading) return;
        const file = fileInput.files[0];
        if (!file) return;
        const errorBox = document.getElementById('upload-error');
        errorBox.hidden = true;
        uploading = true;
        form.querySelector('[type="submit"]').disabled = true;
        uploadDialog.close();
        overlay.showModal();
        progress.value = 0;
        status.textContent = 'Menyiapkan upload...';
        try {
            const metadata = new FormData(form);
            metadata.set('name', file.name);
            metadata.set('size', file.size);
            const start = await send(window.contentLibraryConfig.beginUrl, metadata);
            const base = window.contentLibraryConfig.uploadBase + '/' + start.id;
            let offset = 0;
            while (offset < file.size) {
                const body = new FormData();
                body.set('offset', offset);
                body.set('chunk', file.slice(offset, offset + 1024 * 1024), 'chunk.bin');
                const result = await send(base + '/chunk', body);
                if (!Number.isSafeInteger(result.offset) || result.offset <= offset || result.offset > file.size) throw new Error('Respons upload tidak valid. Silakan coba lagi.');
                offset = result.offset;
                progress.value = Math.floor(offset / file.size * 100);
                status.textContent = progress.value + '% ? ' + formatSize(offset) + ' dari ' + formatSize(file.size);
            }
            status.textContent = 'Memvalidasi dan menyimpan file...';
            await send(base + '/finish', new FormData());
            uploading = false;
            overlay.close();
            success.showModal();
        } catch (error) {
            overlay.close();
            errorBox.textContent = error.message;
            errorBox.hidden = false;
            uploadDialog.showModal();
        } finally {
            uploading = false;
            if (overlay.open) overlay.close();
            form.querySelector('[type="submit"]').disabled = false;
        }
    });
})();
