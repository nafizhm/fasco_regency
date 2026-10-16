@extends('layouts.app')

@section('title', 'Siteplan Penjualan - Dealaska')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif !important;
            background: url('{{ $bg ? asset("config_media/" . $bg->nama_file) : "" }}') no-repeat center center fixed !important;
            background-size: cover !important;
            min-height: 100vh;
        }

        .siteplan-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 15px 50px;
        }

        .siteplan-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.97);
        }

        .siteplan-header {
            background: linear-gradient(135deg, #0d3b66 0%, #1e5fa8 50%, #3a86ff 100%);
            padding: 24px 30px;
            text-align: center;
        }

        .siteplan-header h4 {
            color: #fff;
            font-weight: 700;
            margin: 0;
            font-size: 1.25rem;
            letter-spacing: 0.5px;
        }

        .siteplan-header p {
            color: rgba(255, 255, 255, 0.8);
            margin: 8px 0 0;
            font-size: 0.85rem;
        }

        .siteplan-body {
            padding: 20px 30px 30px;
        }

        /* Tabs Styling */
        .nav-tabs {
            border-bottom: 2px solid #e8f5e9;
            gap: 5px;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #6b7280;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 8px 8px 0 0;
            transition: all 0.2s;
        }

        .nav-tabs .nav-link:hover {
            background-color: #f9fafb;
            color: #1e5fa8;
        }

        .nav-tabs .nav-link.active {
            background-color: transparent;
            color: #1e5fa8;
            border-bottom: 3px solid #1e5fa8;
            font-weight: 700;
        }

        /* SVG Container */
        .svg-card-wrapper {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            margin-top: 20px;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        }

        .svg-view-container {
            width: 100%;
            height: 65vh;
            min-height: 500px;
            overflow: hidden;
            position: relative;
            background: #f8fafc;
        }

        .svg-view-container svg {
            width: 100%;
            height: 100%;
            cursor: grab;
            transition: transform 0.1s ease-out;
            touch-action: none;
            user-select: none;
            -webkit-user-drag: none;
        }

        /* Floating Components */
        .legend-box {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 15px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            width: 210px;
            z-index: 1000;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #e5e7eb;
        }

        .legend-box.hidden {
            transform: translateX(250px);
        }

        .legend-toggle {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 45px;
            height: 45px;
            background: #1e5fa8;
            color: #fff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            cursor: pointer;
            z-index: 999;
            border: none;
        }

        .legend-title {
            font-weight: 700;
            font-size: 0.85rem;
            color: #111827;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f3f4f6;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            border: 1px solid rgba(0,0,0,0.1);
            flex-shrink: 0;
        }

        .legend-label {
            font-size: 0.75rem;
            color: #4b5563;
            font-weight: 500;
        }

        /* Popup Box */
        #popupOverlay {
            position: fixed;
            z-index: 9999;
            display: none;
            pointer-events: none;
        }

        #popupBox {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            width: min(300px, calc(100vw - 30px));
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            position: relative;
            pointer-events: all;
            border: 1px solid #e5e7eb;
        }

        #popupClose {
            position: absolute;
            top: 10px;
            right: 12px;
            background: #f3f4f6;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
        }

        .popup-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: #1a5c30;
            margin-bottom: 15px;
        }

        .popup-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.8rem;
        }

        .popup-label {
            color: #6b7280;
        }

        .popup-value {
            font-weight: 600;
            color: #111827;
            text-align: right;
        }

        .popup-price {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .popup-price .value {
            font-size: 1rem;
            font-weight: 800;
            color: #16a34a;
        }

        .btn-reset {
            position: absolute;
            top: 15px;
            left: 15px;
            z-index: 10;
            background: rgba(255,255,255,0.9);
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #374151;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.2s;
        }

        .btn-reset:hover {
            background: #fff;
            border-color: #1e5fa8;
            color: #1e5fa8;
        }

        .text-white-svg text {
            fill: #ffffff !important;
        }

        .popup-arrow { position: absolute; width: 0; height: 0; border-left: 10px solid transparent; border-right: 10px solid transparent; }
        .popup-connector { position: fixed; inset: 0; width: 100vw; height: 100vh; overflow: visible; pointer-events: none; }
        .status-badge { border-radius: 999px; padding: 3px 10px; font-size: 0.75rem; }
        .status-ready { background: #dcfce7; color: #166534; }
        .status-hold { background: #d1d5db; color: #374151; }
        .status-sold { background: #dc2626; color: #fff; }
        .detail-button.selected-unit > path, .detail-button.selected-unit > polygon { stroke: #00e5ff; stroke-width: 110; paint-order: stroke fill; }
        .siteplan-compass { position: absolute; bottom: 14px; right: 14px; width: 100px; height: 100px; z-index: 5; pointer-events: none; background: rgba(255,255,255,.85); border-radius: 50%; box-shadow: 0 2px 10px #0002; }

        @media (max-width: 768px) {
            .siteplan-container {
                padding: 15px 10px;
            }
            .siteplan-header {
                padding: 20px;
            }
            .siteplan-body {
                padding: 15px;
            }
            .svg-view-container {
                height: 50vh;
                min-height: 340px;
            }
            .siteplan-compass { width: 72px; height: 72px; bottom: 10px; right: 10px; }
            .nav-tabs { flex-wrap: nowrap; overflow-x: auto; }
            .nav-tabs .nav-link { white-space: nowrap; }
            #popupBox { padding: 15px; }
            .legend-box {
                bottom: 80px;
                right: 20px;
            }
            .legend-toggle {
                bottom: 20px;
                right: 20px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="siteplan-container">
        <div class="siteplan-card">
            {{-- Header (Matching Booking) --}}
            <div class="siteplan-header">
                <h4>Siteplan Penjualan</h4>
                <p>Silakan pilih lokasi perumahan dan klik pada kavling untuk melihat detail informasi.</p>
            </div>

            <div class="siteplan-body">
                {{-- Tabs --}}
                <ul class="nav nav-tabs" id="siteplan-tabs" role="tablist">
                    @foreach ($lokasiKavling as $index => $kav)
                        <li class="nav-item">
                            <a class="nav-link {{ $index == 0 ? 'active' : '' }}" id="tab-{{ $kav->id }}"
                                data-toggle="pill" href="#pane-{{ $kav->id }}" role="tab">
                                {{ $kav->nama_kavling }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="tab-content" id="siteplan-tabContent">
                    @foreach ($lokasiKavling as $index => $kav)
                        <div class="tab-pane fade {{ $index == 0 ? 'show active' : '' }}" id="pane-{{ $kav->id }}"
                            role="tabpanel">

                            <div class="svg-card-wrapper">
                                <div class="svg-view-container svg-container" id="svg-container-{{ $kav->id }}" data-fit-siteplan="true">
                                    <button class="btn btn-reset reset-button">
                                        <i class="fas fa-sync-alt mr-1"></i> Reset Siteplan
                                    </button>

                                    {{-- SVG Render --}}
                                    @if ($kav->masterSvg)
                                        @php
                                            $svgHeader = str_replace(
                                                ['[[lebar]]', '[[tinggi]]'],
                                                ['100%', '100%'],
                                                $kav->masterSvg->header_svg
                                            );
                                            $svgHeader = preg_replace_callback(
                                                '~((?:xlink:)?href\\s*=\\s*)(["\'])[^"]*?siteplan_ImgID1\\.png\\2~i',
                                                fn ($match) => $match[1] . $match[2] . e(asset('svg/siteplan_Images/siteplan_ImgID1.png')) . $match[2],
                                                $svgHeader
                                            );
                                        @endphp
                                        {!! $svgHeader !!}

                                        @foreach ($kav->kavlingPeta as $pt)
                                            @php
                                                $warna = $pt->public_color;
                                            @endphp

                                            <a href="javascript:void(0);" class="detail-button {{ $pt->public_status === 'Sold' ? 'text-white-svg' : '' }}"
                                                data-status="{{ $pt->public_status }}" data-cluster="{{ $pt->nama_cluster }}"
                                                data-tooltip="{{ json_encode([
                                                    'public_status' => $pt->public_status,
                                                    'nama_cluster' => $pt->nama_cluster,
                                                    'kode_kavling' => $pt->kode_kavling,
                                                    'tipe_bangunan' => $pt->tipe_bangunan,
                                                    'luas_tanah' => $pt->luas_tanah,
                                                    'luas_bangunan' => $pt->luas_bangunan,
                                                    'total_harga' => $pt->total_harga,
                                                ], JSON_UNESCAPED_UNICODE) }}"
                                                data-url="{{ route('public.siteplan.show', $pt->id) }}">
                                                {!! str_replace(
                                                    ['[[1]]', '[[2]]', '[[3]]', '[[4]]'],
                                                    [$pt->map, $warna, $pt->matrik, $pt->kode_kavling],
                                                    $pt->jenis_map == 'polygon' ? $kav->masterSvg->polygon_svg : $kav->masterSvg->path_svg,
                                                ) !!}
                                            </a>
                                        @endforeach

                                        {!! $kav->masterSvg->footer_svg !!}
                                    @endif
                                    <img class="siteplan-compass" src="{{ asset('svg/mata_angin.svg') }}" alt="Mata angin Utara Timur Selatan Barat">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Legend --}}
    <button class="legend-toggle" onclick="toggleLegend()">
        <i class="fas fa-info-circle"></i>
    </button>
    <div class="legend-box hidden" id="legendBox">
    <div class="legend-title">Keterangan Status</div>

        @foreach ($legend as $item)
            <div class="legend-item">
                <div class="legend-color" style="background-color: {{ $item->warna }}"></div>
                <div class="legend-label">{{ $item->status_progres }}</div>
            </div>
        @endforeach

        <div class="legend-title mt-3">Warna Cluster</div>
        @foreach ($clusterColors as $cluster => $color)
            <div class="legend-item">
                <div class="legend-color" style="background-color: {{ $color }}"></div>
                <div class="legend-label">{{ ucfirst(strtolower($cluster)) }}</div>
            </div>
        @endforeach

        <button class="btn btn-xs btn-block mt-3 text-muted" onclick="toggleLegend()">Tutup</button>
    </div>

    {{-- Detail Popup --}}
    <div id="popupOverlay">
        <svg class="popup-connector" aria-hidden="true">
            <defs><marker id="unit-arrow" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto"><path d="M0,0 L8,4 L0,8 Z" fill="#0284c7"/></marker></defs>
            <line id="popupConnector" stroke="#0284c7" stroke-width="2" marker-end="url(#unit-arrow)"/>
        </svg>
        <div id="popupBox">
            <button id="popupClose" onclick="closePopup()">&times;</button>
            <div class="popup-arrow"></div>

            <div class="popup-title">Detail Kavling</div>

            <div class="popup-row" id="p_status_row">
                <span class="popup-label">Status</span>
                <span class="popup-value status-badge" id="p_status">-</span>
            </div>

            <div class="popup-row">
                <span class="popup-label">Cluster</span>
                <span class="popup-value" id="p_cluster">-</span>
            </div>
            <div class="popup-row">
                <span class="popup-label">Kode Kavling</span>
                <span class="popup-value" id="p_kode_kavling">-</span>
            </div>
            <div class="popup-row">
                <span class="popup-label">Tipe / Luas Bangunan</span>
                <span class="popup-value"><span id="p_tipe_bangunan">-</span> / <span id="p_luas_bangunan">-</span> m&sup2;</span>
            </div>
            <div class="popup-row">
                <span class="popup-label">Luas Tanah</span>
                <span class="popup-value"><span id="p_luas_tanah">-</span> m²</span>
            </div>

            <div class="popup-price">
                <span class="popup-label font-weight-bold" id="p_price_label">Total Harga</span>
                <span class="value" id="p_price_value"><span id="p_currency">Rp</span> <span id="p_hrg_jual">0</span></span>
                <span class="status-badge status-sold" id="p_availability" style="display: none"></span>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let currentTargetElement = null;
        let popupUpdateInterval = null;

        function toggleLegend() {
            $('#legendBox').toggleClass('hidden');
        }

        function updatePopupPosition() {
            if (!currentTargetElement || !$('#popupOverlay').is(':visible')) return;

            const targetRect = currentTargetElement.getBoundingClientRect();
            const popupBox = document.getElementById('popupBox');
            const popupWidth = popupBox.offsetWidth;
            const popupHeight = popupBox.offsetHeight;

            const targetCenterX = targetRect.left + (targetRect.width / 2);
            const targetCenterY = targetRect.top + (targetRect.height / 2);

            let left = targetCenterX - (popupWidth / 2);
            let top = targetCenterY - popupHeight - 25;

            const margin = 15;
            const winW = $(window).width();
            const winH = $(window).height();

            if (left < margin) left = margin;
            else if (left + popupWidth > winW - margin) left = winW - popupWidth - margin;

            if (top < margin) {
                top = targetCenterY + 25;
                $('.popup-arrow').css({
                    'bottom': 'auto', 'top': '-10px',
                    'border-top': 'none', 'border-bottom': '10px solid #fff'
                });
            } else {
                $('.popup-arrow').css({
                    'bottom': '-10px', 'top': 'auto',
                    'border-top': '10px solid #fff', 'border-bottom': 'none'
                });
            }

            top = Math.max(margin, Math.min(top, winH - popupHeight - margin));
            const below = top + popupHeight / 2 > targetCenterY;
            const arrowX = Math.max(20, Math.min(targetCenterX - left, popupWidth - 20));
            $('.popup-arrow').css({ left: (arrowX - 10) + 'px', top: below ? '-10px' : 'auto', bottom: below ? 'auto' : '-10px', 'border-top': below ? 'none' : '10px solid #fff', 'border-bottom': below ? '10px solid #fff' : 'none' });
            $('#popupConnector').attr({ x1: left + arrowX, y1: below ? top - 10 : top + popupHeight + 10, x2: targetCenterX, y2: targetCenterY });

            $('#popupOverlay').css({ left: left + 'px', top: top + 'px' });
        }

        $(document).on('click', '.detail-button', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const data = $(this).data('tooltip');
            if (!data) return;
            $('#popupOverlay').stop(true, true).hide();
            if (popupUpdateInterval) clearInterval(popupUpdateInterval);
            currentTargetElement = this;
            $('.detail-button').removeClass('selected-unit');
            $(this).addClass('selected-unit');
            $('#p_status').text(data.public_status).removeClass('status-ready status-hold status-sold').addClass('status-' + data.public_status.toLowerCase());
            $('#p_cluster').text(data.nama_cluster || '-');
            $('#p_kode_kavling').text(data.kode_kavling);
            $('#p_tipe_bangunan').text(data.tipe_bangunan);
            $('#p_luas_tanah').text(data.luas_tanah);
            $('#p_luas_bangunan').text(data.luas_bangunan);
            const unavailable = data.public_status === 'Sold' || data.public_status === 'Hold';
            $('#p_status_row').toggle(!unavailable);
            $('#p_price_label, #p_price_value').toggle(!unavailable);
            $('#p_availability').text(data.public_status === 'Sold' ? 'Sold Out' : 'Hold').toggle(unavailable);
            if (!unavailable && data.total_harga > 0) {
                $('#p_hrg_jual').text(parseFloat(data.total_harga).toLocaleString('id-ID'));
                $('#p_currency').show();
                $('.popup-price').show();
            } else if (!unavailable) {
                $('#p_hrg_jual').text('Harga belum tersedia');
                $('#p_currency').hide();
            }

            $('#popupOverlay').css('opacity', 0).show();
            updatePopupPosition();
            $('#popupOverlay').css('opacity', 1);
            popupUpdateInterval = setInterval(updatePopupPosition, 50);
        });

        function closePopup() {
            $('#popupOverlay').stop(true, true).hide();
            currentTargetElement = null;
            $('.detail-button').removeClass('selected-unit');
            if (popupUpdateInterval) clearInterval(popupUpdateInterval);
        }

        $(document).on('click', function(e) {
            if ($(e.target).closest('#popupBox, .detail-button').length === 0) {
                closePopup();
            }
        });

        $(window).on('resize scroll', updatePopupPosition);
        $('a[data-toggle="pill"]').on('shown.bs.tab', closePopup);

        // Auto-update position on SVG zoom/pan
        if (window.MutationObserver) {
            const observer = new MutationObserver(() => {
                if ($('#popupOverlay').is(':visible')) updatePopupPosition();
            });
            $(document).ready(() => {
                $('svg').each(function() {
                    observer.observe(this, { attributes: true, attributeFilter: ['transform', 'style'] });
                });
            });
        }
    </script>
    <script src="{{ asset('assets/svg_1.js') }}"></script>
@endpush
