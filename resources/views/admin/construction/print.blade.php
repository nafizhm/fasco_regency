<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Progres Pembangunan - {{ $record->kode_kavling }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #edf1f5; color: #182c3f; font: 14px/1.6 Arial, sans-serif; }
        .toolbar { padding: 14px 24px; background: #fff; border-bottom: 1px solid #ddd; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        button { background: #1d4ed8; color: white; border: 0; border-radius: 6px; padding: 10px 18px; cursor: pointer; }
        main { max-width: 900px; margin: 24px auto; padding: 36px; background: white; }
        header { border-bottom: 2px solid #182c3f; padding-bottom: 18px; margin-bottom: 24px; }
        h1 { font-size: 24px; margin: 0 0 8px; } h2 { font-size: 17px; margin: 0; }
        p { margin: 6px 0; } .muted { color: #526477; }
        article { margin-bottom: 28px; } .report-heading { display: flex; justify-content: space-between; gap: 12px; border-bottom: 1px solid #ddd; padding-bottom: 8px; break-after: avoid; }
        .percentage { font-weight: bold; } .notes { white-space: pre-wrap; overflow-wrap: anywhere; }
        .photos { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        figure { margin: 8px 0; break-inside: avoid; } img { display: block; width: 100%; height: 220px; object-fit: contain; }
        figcaption { font-size: 11px; overflow-wrap: anywhere; color: #526477; }
        .photo-error { padding: 20px; border: 1px dashed #aaa; color: #92400e; }
        button:disabled { opacity: .6; cursor: wait; }
        @page { size: A4; margin: 15mm; }
        @media print { body { background: white; color: black; } .toolbar { display: none; } main { margin: 0; padding: 0; max-width: none; } img { height: 65mm; } }
        @media screen and (max-width: 600px) { main { padding: 20px; margin: 12px; } .photos { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="toolbar"><span id="print-status" role="status">Menyiapkan foto laporan...</span><button id="print-button" type="button" disabled>Cetak / Simpan PDF</button></div>
    <noscript><p>Aktifkan JavaScript untuk memuat foto dan mencetak laporan ini.</p></noscript>
    <main>
        <header>
            <h1>Laporan Progres Pembangunan</h1>
            <p><strong>Unit {{ $record->kode_kavling }}</strong> &middot; {{ $record->nama_kavling }} @if ($record->nama_cluster) &middot; Cluster {{ $record->nama_cluster }} @endif</p>
            <p class="muted">{{ $reports->count() }} laporan &middot; {{ \Carbon\Carbon::parse($reports->first()->reported_at)->locale('id')->translatedFormat('d F Y') }} s.d. {{ \Carbon\Carbon::parse($reports->last()->reported_at)->locale('id')->translatedFormat('d F Y') }}</p>
            <p class="muted">Dicetak {{ now('Asia/Jakarta')->translatedFormat('d F Y H:i') }} WIB</p>
        </header>
        @foreach ($reports as $report)
            <article>
                <div class="report-heading"><h2>{{ $loop->iteration }}. {{ \Carbon\Carbon::parse($report->reported_at)->locale('id')->translatedFormat('d F Y') }}</h2><span class="percentage">Progres {{ (float) $report->percentage }}%</span></div>
                <p class="notes">{{ $report->notes }}</p>
                <div class="photos">
                    @foreach (json_decode($report->photos, true) ?? [] as $index => $photo)
                        <figure><img data-src="{{ route('construction.photo', [$report->id, $index], false) }}" alt="{{ $photo['name'] }}"><figcaption>{{ $photo['name'] }}</figcaption></figure>
                    @endforeach
                </div>
            </article>
        @endforeach
    </main>
    <script>
        (async function () {
            const photos = Array.from(document.querySelectorAll('img[data-src]'));
            const status = document.getElementById('print-status');
            const button = document.getElementById('print-button');
            let failed = 0;
            for (const [index, photo] of photos.entries()) {
                status.textContent = `Memuat foto ${index + 1} dari ${photos.length}...`;
                const loaded = await new Promise(resolve => {
                    const finish = success => {
                        clearTimeout(timeout);
                        photo.onload = photo.onerror = null;
                        if (!success) photo.removeAttribute('src');
                        resolve(success);
                    };
                    const timeout = setTimeout(() => finish(false), 15000);
                    photo.onload = () => finish(true);
                    photo.onerror = () => finish(false);
                    photo.src = photo.dataset.src;
                });
                if (!loaded) {
                    failed++;
                    const message = document.createElement('p');
                    message.className = 'photo-error';
                    message.textContent = 'Foto gagal dimuat. Buka ulang halaman untuk mencoba lagi.';
                    photo.replaceWith(message);
                }
            }
            status.textContent = failed
                ? `${failed} foto gagal dimuat. Anda dapat mencetak laporan atau muat ulang untuk mencoba lagi.`
                : 'Laporan siap. Klik Cetak / Simpan PDF.';
            button.disabled = false;
            button.addEventListener('click', () => window.print());
        })();
    </script>
</body>
</html>
