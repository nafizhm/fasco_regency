<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>{{ $report['title'] }} - {{ $report['label'] }}</title>
<style>
    @page { margin: 20px; }
    body { margin: 0; }
    .tukang-report { font-size: {{ $report['type'] === 'bulanan' ? 8 : 9 }}px !important; line-height: 1.2; }
    .tukang-report th, .tukang-report td { padding: {{ $report['type'] === 'bulanan' ? '2px' : '4px 3px' }} !important; }
    .tukang-report .report-note { font-size: 8px !important; }
    .tukang-report small { font-size: 7px !important; }
</style>
</head>
<body>@include('admin.pengaturan.tukang.laporan-table')</body>
</html>
