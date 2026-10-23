(() => {
    const config = document.getElementById('build-config');
    if (!config) return;
    const base = config.dataset.base;
    const history = document.getElementById('build-history-dialog');
    const items = document.getElementById('build-history-items');
    const more = document.getElementById('build-history-more');
    const lightbox = document.getElementById('build-lightbox');
    const summary = document.getElementById('build-history-summary');
    let controller, historyUnit, nextPage, uploading = false, loading = false;
    const size = bytes => (bytes / 1048576).toFixed(2) + ' MB';
    const date = value => new Date(value + 'T12:00:00').toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'});
    function node(tag, className, text) {
        const result = document.createElement(tag);
        if (className) result.className = className;
        if (text !== undefined) result.textContent = text;
        return result;
    }
    document.addEventListener('click', event => {
        const close = event.target.closest('[data-build-close]');
        if (close) close.closest('dialog').close();
        const trigger = event.target.closest('[data-build-history]');
        if (trigger) { event.preventDefault(); openHistory(trigger.dataset.buildHistory); }
    });
    document.querySelectorAll('.build-dialog').forEach(dialog => dialog.addEventListener('click', event => {
        if (uploading || event.target !== dialog || dialog.id === 'build-success') return;
        const rect = dialog.getBoundingClientRect();
        if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
    }));
    history.addEventListener('close', () => controller?.abort());
    lightbox.addEventListener('close', () => document.getElementById('build-lightbox-image').removeAttribute('src'));
    function render(report, canDelete) {
        const article = node('article', 'build-timeline-entry');
        const heading = node('div', 'build-timeline-heading');
        heading.append(node('strong', '', date(report.reported_at)), node('span', 'build-percentage-badge', Number(report.percentage) + '%'));
        article.append(heading);
        const meter = node('div', 'build-meter');
        const fill = node('span');
        fill.style.width = Number(report.percentage) + '%';
        meter.append(fill);
        article.append(meter, node('p', 'build-timeline-notes', report.notes));
        const photos = node('div', 'build-history-photos');
        report.photos.forEach(photo => {
            const button = node('button', 'build-history-photo');
            button.type = 'button';
            button.setAttribute('aria-label', 'Lihat foto ' + photo.name);
            const image = node('img');
            image.src = photo.url;
            image.alt = photo.name;
            image.loading = 'lazy';
            image.addEventListener('error', () => { image.hidden = true; button.append(node('span', '', 'Buka foto asli')); }, {once: true});
            button.append(image);
            button.addEventListener('click', () => {
                const original = document.getElementById('build-lightbox-image');
                original.src = photo.url;
                original.alt = photo.name;
                document.getElementById('build-lightbox-link').href = photo.url;
                lightbox.showModal();
            });
            photos.append(button);
        });
        article.append(photos);
        if (canDelete) {
            const remove = node('button', 'build-button build-delete', 'Hapus laporan');
            remove.type = 'button';
            remove.addEventListener('click', async () => {
                if (!window.confirm('Hapus laporan tanggal ' + date(report.reported_at) + ' beserta fotonya? Tindakan ini tidak dapat dibatalkan.')) return;
                remove.disabled = true;
                try {
                    const response = await fetch(base + '/reports/' + report.id, {method: 'DELETE', headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}});
                    if (!response.ok) throw new Error('Laporan gagal dihapus. Periksa hak akses dan coba lagi.');
                    const url = new URL(window.location.href);
                    url.searchParams.set('unit', historyUnit);
                    window.location.assign(url.href);
                } catch (error) { window.alert(error.message); remove.disabled = false; }
            });
            article.append(remove);
        }
        items.append(article);
    }
    async function load(page = 1) {
        const current = controller;
        loading = true;
        more.disabled = true;
        try {
            const response = await fetch(base + '/units/' + historyUnit + '/history?page=' + page, {headers: {'Accept': 'application/json'}, signal: current.signal});
            if (!response.ok) throw new Error(response.status === 403 ? 'Anda belum memiliki akses Progres Pembangunan. Hubungi administrator.' : 'Riwayat belum dapat dimuat. Silakan coba lagi.');
            const data = await response.json();
            if (current !== controller) return;
            document.getElementById('build-history-title').textContent = 'Unit ' + data.unit.kode_kavling;
            document.getElementById('build-history-location').textContent = [data.unit.nama_kavling, 'Cluster ' + (data.unit.nama_cluster?.trim() || 'belum diisi')].filter(Boolean).join(' / ');
            if (page === 1) {
                items.replaceChildren();
                const latest = data.reports.data[0];
                const info = node('div');
                info.append(node('span', '', 'Progres terbaru'), node('strong', '', latest ? Number(latest.percentage) + '%' : 'Belum dilaporkan'));
                summary.replaceChildren(info, node('span', '', data.reports.total + ' laporan tersimpan'));
                if (!latest) items.append(node('p', 'build-history-empty', 'Belum ada laporan pembangunan untuk unit ini.'));
            }
            data.reports.data.forEach(report => render(report, data.can_delete));
            nextPage = data.reports.next_page_url ? data.reports.current_page + 1 : null;
            more.hidden = !nextPage;
            more.textContent = 'Muat laporan sebelumnya';
        } catch (error) {
            if (error.name !== 'AbortError' && current === controller) {
                if (page === 1) items.replaceChildren(node('p', 'build-alert', error.message));
                nextPage = page;
                more.hidden = false;
                more.textContent = 'Coba muat kembali';
            }
        } finally {
            if (current === controller) { loading = false; more.disabled = false; }
        }
    }
    function openHistory(unit) {
        controller?.abort();
        controller = new AbortController();
        historyUnit = unit;
        nextPage = null;
        summary.replaceChildren();
        more.hidden = true;
        document.getElementById('build-history-title').textContent = 'Riwayat pembangunan';
        document.getElementById('build-history-location').textContent = '';
        items.replaceChildren(node('p', 'build-history-empty', 'Memuat riwayat dan dokumentasi...'));
        if (!history.open) history.showModal();
        load();
    }
    more.addEventListener('click', () => { if (!loading && nextPage) load(nextPage); });
    const mapSelect = document.getElementById('build-map-location');
    if (mapSelect) {
        const states = new Map();
        function apply(canvas) {
            const state = states.get(canvas);
            const drawing = canvas.querySelector('.build-map-drawing');
            if (drawing) drawing.style.transform = `translate(${state.x}px,${state.y}px) scale(${state.scale})`;
        }
        function zoom(canvas, factor, x, y) {
            const state = states.get(canvas);
            const nextScale = Math.min(5, Math.max(.5, state.scale * factor));
            const ratio = nextScale / state.scale;
            state.x = x - (x - state.x) * ratio;
            state.y = y - (y - state.y) * ratio;
            state.scale = nextScale;
            apply(canvas);
        }
        function changeMap() { document.querySelectorAll('[data-map-location]').forEach(canvas => canvas.hidden = canvas.dataset.mapLocation !== mapSelect.value); }
        mapSelect.addEventListener('change', changeMap);
        changeMap();
        document.querySelectorAll('.build-map-canvas').forEach(canvas => {
            states.set(canvas, {x: 0, y: 0, scale: 1});
            canvas.addEventListener('wheel', event => {
                if (!canvas.querySelector('.build-map-drawing') || start) return;
                event.preventDefault();
                const bounds = canvas.getBoundingClientRect();
                const delta = event.deltaY * (event.deltaMode === 1 ? 16 : event.deltaMode === 2 ? canvas.clientHeight : 1);
                zoom(canvas, Math.exp(-Math.max(-150, Math.min(150, delta)) * .002), event.clientX - bounds.left, event.clientY - bounds.top);
            }, {passive: false});
            let start, moved = false, suppress = false;
            canvas.addEventListener('pointerdown', event => {
                if (event.target.closest('button') || (event.pointerType === 'mouse' && event.button !== 0)) return;
                const state = states.get(canvas);
                start = {x: event.clientX, y: event.clientY, originalX: state.x, originalY: state.y, id: event.pointerId};
                moved = false;
            });
            canvas.addEventListener('pointermove', event => {
                if (!start || event.pointerId !== start.id) return;
                const deltaX = event.clientX - start.x, deltaY = event.clientY - start.y;
                if (Math.abs(deltaX) + Math.abs(deltaY) < 9 && !moved) return;
                moved = true;
                canvas.setPointerCapture(event.pointerId);
                const state = states.get(canvas);
                state.x = start.originalX + deltaX;
                state.y = start.originalY + deltaY;
                apply(canvas);
            });
            function end() { if (moved) { suppress = true; setTimeout(() => suppress = false, 100); } start = null; }
            canvas.addEventListener('pointerup', end);
            canvas.addEventListener('pointercancel', end);
            canvas.addEventListener('click', event => { if (suppress) { event.preventDefault(); event.stopPropagation(); } }, true);
            canvas.querySelectorAll('[data-map-zoom]').forEach(button => button.addEventListener('click', () => {
                zoom(canvas, Number(button.dataset.mapZoom), canvas.clientWidth / 2, canvas.clientHeight / 2);
            }));
        });
        document.getElementById('build-map-reset').addEventListener('click', () => {
            const canvas = document.querySelector('.build-map-canvas:not([hidden])');
            if (canvas) { states.set(canvas, {x: 0, y: 0, scale: 1}); apply(canvas); }
        });
        document.querySelectorAll('[data-build-view]').forEach(button => button.addEventListener('click', () => {
            const map = button.dataset.buildView === 'map';
            document.getElementById('build-cards').hidden = map;
            document.getElementById('build-map').hidden = !map;
            document.querySelectorAll('[data-build-view]').forEach(toggle => { toggle.classList.toggle('is-active', toggle === button); toggle.setAttribute('aria-pressed', String(toggle === button)); });
        }));
    }
    const initialUnit = new URLSearchParams(location.search).get('unit');
    if (initialUnit && /^\d+$/.test(initialUnit)) openHistory(initialUnit);
    const form = document.getElementById('build-upload-form');
    if (!form) return;
    const dialog = document.getElementById('build-upload-dialog');
    const overlay = document.getElementById('build-upload-progress');
    const success = document.getElementById('build-success');
    const locationSelect = document.getElementById('build-location');
    const clusterSelect = document.getElementById('build-cluster');
    const unitSelect = document.getElementById('build-unit');
    const photoList = document.getElementById('build-selected-photos');
    const percentage = document.getElementById('build-percentage');
    const range = document.getElementById('build-percentage-range');
    const percentageHint = document.getElementById('build-percentage-hint');
    let percentageController;
    async function updatePercentage() {
        percentageController?.abort();
        const current = percentageController = new AbortController();
        const unit = unitSelect.value;
        percentage.disabled = range.disabled = true;
        percentage.value = range.value = 0;
        percentage.min = range.min = 0;
        percentage.max = range.max = 100;
        percentageHint.textContent = unit ? 'Memuat progres terakhir...' : 'Pilih unit untuk melihat batas persentase.';
        if (!unit) return;
        try {
            const response = await fetch(base + '/units/' + unit + '/history', {headers: {'Accept': 'application/json'}, signal: current.signal});
            if (!response.ok) throw new Error('Progres terakhir belum dapat dimuat. Pilih ulang unit untuk mencoba kembali.');
            const data = await response.json();
            if (current !== percentageController) return;
            const latest = data.reports.data[0];
            const minimum = latest ? Number(latest.percentage) : 0;
            percentage.min = minimum;
            range.min = 0;
            percentage.max = range.max = 100;
            percentage.value = range.value = minimum;
            range.step = '0.01';
            percentage.disabled = range.disabled = false;
            percentageHint.textContent = latest ? 'Progres terakhir ' + minimum + '%. Laporan ini tidak boleh lebih rendah dari angka tersebut.' : 'Belum ada laporan. Persentase awal dapat diisi 0-100%.';
            document.getElementById('build-percentage-limit').textContent = '100% - Selesai';
        } catch (error) {
            if (error.name !== 'AbortError' && current === percentageController) percentageHint.textContent = error.message;
        }
    }
    unitSelect.addEventListener('change', updatePercentage);
    const progress = document.getElementById('build-progress-bar');
    const status = document.getElementById('build-progress-text');
    let files = [], urls = [], savedUnit, uploadSession;
    function filterUnits() {
        unitSelect.value = '';
        unitSelect.disabled = !locationSelect.value || !clusterSelect.value;
        unitSelect.options[0].textContent = !locationSelect.value ? 'Pilih lokasi terlebih dahulu' : (!clusterSelect.value ? 'Pilih cluster terlebih dahulu' : 'Pilih unit / kavling');
        Array.from(unitSelect.options).slice(1).forEach(option => {
            option.hidden = option.dataset.location !== locationSelect.value || option.dataset.cluster !== clusterSelect.value;
            option.disabled = option.hidden;
        });
        updatePercentage();
    }
    function filterClusters() {
        clusterSelect.replaceChildren(new Option(locationSelect.value ? 'Pilih cluster' : 'Pilih lokasi terlebih dahulu', ''));
        clusterSelect.disabled = !locationSelect.value;
        const clusters = new Set(Array.from(unitSelect.options).filter(option => option.dataset.location === locationSelect.value).map(option => option.dataset.cluster));
        [...clusters].sort((a, b) => a.localeCompare(b, 'id')).forEach(cluster => clusterSelect.add(new Option(cluster === '__unassigned' ? 'Tanpa cluster' : cluster, cluster)));
        filterUnits();
    }
    locationSelect.addEventListener('change', filterClusters);
    clusterSelect.addEventListener('change', filterUnits);
    filterClusters();
    document.querySelectorAll('[data-build-upload]').forEach(button => button.addEventListener('click', () => {
        if (button.dataset.unit) {
            const option = Array.from(unitSelect.options).find(item => item.value === button.dataset.unit);
            if (option) { locationSelect.value = option.dataset.location; filterClusters(); clusterSelect.value = option.dataset.cluster; filterUnits(); unitSelect.value = option.value; updatePercentage(); }
        }
        dialog.showModal();
    }));
    function constrainPercentage() {
        const minimum = Number(percentage.min);
        const maximum = Number(percentage.max);
        const value = percentage.valueAsNumber;
        percentage.value = Number.isFinite(value) ? Math.min(maximum, Math.max(minimum, value)) : minimum;
        range.value = percentage.value;
    }
    percentage.addEventListener('input', constrainPercentage);
    percentage.addEventListener('change', constrainPercentage);
    range.addEventListener('input', () => { range.value = Math.max(Number(percentage.min), Number(range.value)); percentage.value = range.value; });
    function renderFiles() {
        urls.forEach(url => URL.revokeObjectURL(url));
        urls = [];
        photoList.replaceChildren();
        files.forEach((file, index) => {
            const card = node('div', 'build-selected-photo');
            const url = URL.createObjectURL(file);
            urls.push(url);
            const image = node('img');
            image.src = url;
            image.alt = file.name;
            image.addEventListener('error', () => { image.hidden = true; card.prepend(node('span', 'build-photo-fallback', 'Foto')); }, {once: true});
            const remove = node('button', '', '\u00d7');
            remove.type = 'button';
            remove.setAttribute('aria-label', 'Hapus ' + file.name);
            remove.addEventListener('click', () => { files.splice(index, 1); renderFiles(); });
            card.append(image, remove, node('span', '', size(file.size)));
            photoList.append(card);
        });
    }
    [document.getElementById('build-photos'), document.getElementById('build-camera')].forEach(input => input.addEventListener('change', () => {
        const error = document.getElementById('build-upload-error');
        if (files.length + input.files.length > 50) { error.textContent = 'Maksimal 50 foto per laporan. Buat laporan tambahan jika diperlukan.'; error.hidden = false; }
        else { files.push(...input.files); error.hidden = true; renderFiles(); }
        input.value = '';
    }));
    window.addEventListener('beforeunload', event => { if (uploading) { event.preventDefault(); event.returnValue = ''; } });
    overlay.addEventListener('cancel', event => event.preventDefault());
    const finishSuccess = () => window.location.assign(base + '?unit=' + savedUnit);
    success.addEventListener('cancel', event => { event.preventDefault(); finishSuccess(); });
    document.getElementById('build-success-done').addEventListener('click', finishSuccess);
    async function send(url, body) {
        for (let attempt = 0; attempt < 3; attempt++) {
            try {
                const response = await fetch(url, {method: 'POST', body, headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value}});
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const error = new Error((data.errors ? Object.values(data.errors).flat().join(' ') : data.message) || `Upload gagal (HTTP ${response.status}). Periksa koneksi dan coba lagi.`);
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
        const errorBox = document.getElementById('build-upload-error');
        if (percentage.disabled) { errorBox.textContent = 'Tunggu progres terakhir dimuat atau pilih ulang unit.'; errorBox.hidden = false; return; }
        if (!files.length) { errorBox.textContent = 'Tambahkan minimal satu foto dokumentasi.'; errorBox.hidden = false; return; }
        errorBox.hidden = true;
        const totalBytes = files.reduce((sum, file) => sum + file.size, 0);
        const submit = form.querySelector('[type="submit"]');
        uploading = true;
        submit.disabled = true;
        dialog.close();
        overlay.showModal();
        progress.value = 0;
        status.textContent = 'Menyiapkan laporan...';
        try {
            const metadata = new FormData(form);
            files.forEach((file, index) => { metadata.set(`photos[${index}][name]`, file.name); metadata.set(`photos[${index}][size]`, file.size); });
            const signature = JSON.stringify([...metadata.entries()]);
            if (!uploadSession || uploadSession.signature !== signature) {
                uploadSession = {signature, ...await send(base + '/uploads', metadata), offsets: []};
            }
            const start = uploadSession;
            let completed = 0;
            for (let index = 0; index < files.length; index++) {
                const file = files[index];
                let offset = start.offsets[index] || 0;
                while (offset < file.size) {
                    const body = new FormData();
                    body.set('offset', offset);
                    body.set('chunk', file.slice(offset, offset + 1048576), 'chunk.bin');
                    const response = await send(base + '/uploads/' + start.id + '/photos/' + index, body);
                    if (!Number.isSafeInteger(response.offset) || response.offset <= offset || response.offset > file.size) throw new Error('Respons upload tidak valid. Silakan coba lagi.');
                    offset = response.offset;
                    start.offsets[index] = offset;
                    progress.value = Math.floor((completed + offset) / totalBytes * 100);
                    status.textContent = 'Foto ' + (index + 1) + '/' + files.length + ' / ' + progress.value + '% / ' + size(completed + offset) + ' / ' + size(totalBytes);
                }
                completed += file.size;
            }
            status.textContent = 'Memvalidasi foto dan menyimpan laporan...';
            const result = await send(base + '/uploads/' + start.id + '/finish', new FormData());
            savedUnit = result.unit_id;
            uploading = false;
            overlay.close();
            success.showModal();
        } catch (error) {
            overlay.close();
            errorBox.textContent = error.message + ' Foto dan isian tetap tersedia. Tekan Simpan laporan untuk mencoba kembali.';
            errorBox.hidden = false;
            dialog.showModal();
        } finally {
            uploading = false;
            if (overlay.open) overlay.close();
            submit.disabled = false;
        }
    });
})();
