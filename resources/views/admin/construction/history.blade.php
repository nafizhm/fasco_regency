<dialog id="build-history-dialog" class="build-dialog build-history" aria-labelledby="build-history-title"><div class="build-dialog-header"><div><span class="build-eyebrow">CATATAN PERJALANAN UNIT</span><h2 id="build-history-title">Riwayat pembangunan</h2><p id="build-history-location"></p></div><button class="build-close" data-build-close aria-label="Tutup histori">&times;</button></div><div id="build-history-summary"></div><div class="build-print-tools">
    <label for="build-print-mode">Cetak progres unit ini<select id="build-print-mode"><option value="all">Semua progres dari awal</option><option value="selected">Data tertentu (pilih laporan)</option></select></label>
    <a id="build-print-link" class="build-button" target="_blank" rel="noopener" aria-disabled="true"><i class="fas fa-print" aria-hidden="true"></i> Cetak progres</a>
    <p id="build-print-hint" aria-live="polite">Memuat laporan...</p>
</div><div id="build-history-items" aria-live="polite"></div><button id="build-history-more" class="build-button build-light" hidden>Muat laporan sebelumnya</button></dialog>
<dialog id="build-lightbox" class="build-dialog build-lightbox" aria-label="Foto pembangunan"><button class="build-close" data-build-close aria-label="Tutup foto">&times;</button><img id="build-lightbox-image" alt="Foto progres pembangunan"><a id="build-lightbox-link" target="_blank" rel="noopener">Buka foto asli <i class="fas fa-arrow-up-right-from-square"></i></a></dialog>
<div id="build-config" hidden data-base="{{ url('admin/progres-pembangunan') }}"></div>
<script>
    (() => {
        // Keep printing all reports available even if an older history script is cached.
        let unit = new URLSearchParams(window.location.search).get('unit');
        const base = @json(url('admin/progres-pembangunan'));
        const link = document.getElementById('build-print-link');
        document.addEventListener('click', event => {
            const trigger = event.target.closest('[data-build-history]');
            if (trigger) unit = trigger.dataset.buildHistory;
        }, true);
        link.addEventListener('click', event => {
            if (document.getElementById('build-print-mode').value !== 'all') return;
            if (!unit || !/^\d+$/.test(unit)) {
                event.preventDefault();
                window.alert('Tutup riwayat lalu pilih kembali unit yang ingin dicetak.');
                return;
            }
            link.href = base + '/units/' + unit + '/print?mode=all';
            link.setAttribute('aria-disabled', 'false');
            event.stopImmediatePropagation();
        }, true);
    })();
</script>
