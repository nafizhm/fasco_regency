<!-- resources/views/admin/bank/index.blade.php -->
@extends('admin.layout_admin')
@section('content')
    <style>
        .legend {
            max-height: calc(100vh - 100px);
            overflow-y: auto;
            position: fixed;
            top: 80px;
            right: 30px;
            padding: 10px;
            font-size: 14px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.4);
            transition: right 0.3s ease;
            width: 200px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            margin-right: 5px;
            border-radius: 50%;
            border: 2px solid #000;
        }

        .toggle-btn {
            width: 100%;
            padding: 5px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            margin-top: 10px;
        }

        .show-btn {
            position: fixed;
            top: 100px;
            right: 30px;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.4);
            cursor: pointer;
            display: none;
        }

        .svg-container {
            width: 100%;
            height: 100vh;
            overflow: hidden;
            border: 2px solid #d1cfcf;
            position: relative;
        }

        .svg-container svg {
            width: 100%;
            height: 100%;
            cursor: grab;
            transition: transform 0.1s ease-out;
        }
    </style>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 font-weight-bold text-lg text-dark">Siteplan</h1>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12 col-12">

                        <!-- ================================================================================================== -->

                        <div class="card card-primary card-outline card-outline-tabs">
                            <div class="card-header">
                                @include('admin.siteplan.selector', ['printRoute' => 'siteplan-penjualan'])
                            </div>

                            <div class="card-body">
                                <div id="siteplan-content">
                                    @foreach ($lokasiKavling as $index => $kav)
                                        <div id="siteplan-location-{{ $kav->id }}">

                                            

                                            {{-- SVG Container khusus lokasi ini --}}
                                            <div class="svg-container mt-3">
                                                <button class="reset-button btn btn-success btn-sm"
                                                    style="position: absolute; top: 10px; left: 10px; z-index: 10;">
                                                    Reset Siteplan
                                                </button>

                                                {{-- SVG Header --}}
                                                @if ($kav->masterSvg)
                                                    {!! str_replace(['[[lebar]]', '[[tinggi]]'], ['100%', '100%'], $kav->masterSvg->header_svg) !!}
                                                @endif

                                                {{-- Loop kavling --}}
                                                @foreach ($kav->kavlingPeta as $pt)
                                                    @php
                                                        $warna = \App\Services\SiteplanColors::sales($pt, $holdColor, request()->boolean('ready_putih'));
                                                    @endphp


                                                    @if ($pt->jenis_map == 'polygon')
                                                        <a href="javascript:void(0);" class="detail-button"
                                                            data-id="{{ $pt->id }}"
                                                            data-url="{{ route('siteplan-penjualan.show', $pt->id) }}">
                                                            {!! str_replace(
                                                                ['[[1]]', '[[2]]', '[[3]]', '[[4]]'],
                                                                [$pt->map, $warna, $pt->matrik, $pt->kode_kavling],
                                                                $kav->masterSvg->polygon_svg,
                                                            ) !!}
                                                        </a>
                                                    @elseif ($pt->jenis_map == 'path')
                                                        <a href="javascript:void(0);" class="detail-button"
                                                            data-id="{{ $pt->id }}"
                                                            data-url="{{ route('siteplan-penjualan.show', $pt->id) }}">
                                                            {!! str_replace(
                                                                ['[[1]]', '[[2]]', '[[3]]', '[[4]]'],
                                                                [$pt->map, $warna, $pt->matrik, $pt->kode_kavling],
                                                                $kav->masterSvg->path_svg,
                                                            ) !!}
                                                        </a>
                                                    @endif
                                                @endforeach

                                                {{-- SVG Footer --}}
                                                @if ($kav->masterSvg)
                                                    {!! $kav->masterSvg->footer_svg !!}
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- /.card -->
                        </div>
                    </div>

                    <!-- ========================================================================================== -->

                    <!-- Tombol show di luar legenda -->
                    <button class="show-btn btn-xs" id="show-btn" onclick="toggleLegend()">Show</button>

                    <!-- Tambahkan legenda -->
                    <div class="legend" id="legend">
                        @if (request()->boolean('ready_putih'))
                            <strong>Belum laku / Ready</strong>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #ffffff"></div>
                                Putih
                            </div>
                        @else
                        <strong>Belum laku / Ready: warna cluster</strong>
                        @foreach (\App\Services\SiteplanColors::CLUSTERS as $cluster => $color)
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: {{ $color }}"></div>
                                {{ $cluster }}
                            </div>
                        @endforeach
                        @endif
                        <hr>
                        <strong>Progres Penjualan</strong>
                        @foreach ($legend as $item)
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: {{ $item->warna }}"></div>
                                {{ $item->status_progres }}
                            </div>
                        @endforeach

                        <!-- Tombol hide -->
                        <button class="toggle-btn" onclick="toggleLegend()">Hide</button>
                    </div>
                    <!-- ========================================================================================== -->

                </div>
            </div>
        </section>
    </div><!-- /.container-fluid -->
    <!-- /.content-wrapper -->

    @include('admin.siteplan.siteplan_penjualan.detail')
    @include('admin.construction.history')
@endsection

@push('scripts')
    <script src="{{ asset('js/construction.js') . '?v=' . filemtime(public_path('js/construction.js')) }}"></script>
    <script src="{{ asset('assets/svg_1.js') }}"></script>
@endpush
@push('css')
    <link rel="stylesheet" href="{{ asset('css/construction.css') . '?v=' . filemtime(public_path('css/construction.css')) }}">
@endpush
