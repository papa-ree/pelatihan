{{--
    Ringkasan PDF satu periode pelatihan.

    Blade mandiri: tanpa layout CMS, Tailwind, atau komponen Alpine — dompdf
    hanya mengerti HTML + CSS dasar.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ __('Laporan Pelatihan') }} - {{ $report->period->name }}</title>
    <style>
        @page { margin: 24px 28px; }
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #1f2937;
            margin: 0;
        }
        h1 { font-size: 16px; margin: 0 0 2px; color: #0f766e; }
        h2 { font-size: 11px; margin: 18px 0 6px; color: #0f766e; }
        .meta { font-size: 9px; color: #6b7280; margin: 0 0 12px; }
        .muted { font-weight: normal; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        table.summary th,
        table.summary td {
            border: 1px solid #e5e7eb;
            padding: 4px 6px;
            text-align: left;
            vertical-align: top;
        }
        table.summary th { width: 32%; background: #f0fdfa; color: #115e59; font-weight: bold; }
        table.data th {
            background: #0f766e;
            color: #ffffff;
            font-size: 8px;
            padding: 4px 3px;
            text-align: left;
            border: 1px solid #0f766e;
        }
        table.data td {
            border: 1px solid #e5e7eb;
            padding: 3px;
            vertical-align: top;
            word-wrap: break-word;
        }
        table.data tbody tr:nth-child(even) { background: #f9fafb; }
        .empty { text-align: center; color: #6b7280; padding: 8px; }
        .col-name { width: 26%; }
        .col-category { width: 14%; }
        .col-members { width: 8%; text-align: center; }
        .col-urgency { width: 8%; text-align: center; }
        .col-status { width: 10%; }
        .col-summary { width: 34%; }
    </style>
</head>
<body>

<h1>{{ __('Laporan Pelatihan') }}</h1>
<p class="meta">
    {{ __('Periode') }}: {{ $report->period->name }} ({{ $report->period->year }})
    &middot; {{ __('Dibuat') }}: {{ now()->timezone(config('app.timezone'))->locale('id')->translatedFormat('d M Y H:i') }}
</p>

<table class="summary">
    <tbody>
    @foreach ($report->summary() as $row)
        <tr>
            <th>{{ $row['label'] }}</th>
            <td>{{ $row['value'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<h2>{{ __('Klaster') }} <span class="muted">({{ count($report->clusterRows()) }})</span></h2>

<table class="data">
    <thead>
    <tr>
        <th class="col-name">{{ __('Klaster') }}</th>
        <th class="col-category">{{ __('Kategori') }}</th>
        <th class="col-members">{{ __('Anggota') }}</th>
        <th class="col-urgency">{{ __('Urgensi') }}</th>
        <th class="col-status">{{ __('Status') }}</th>
        <th class="col-summary">{{ __('Ringkasan') }}</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($report->clusterRows() as $row)
        <tr>
            <td>{{ $row['name'] }}</td>
            <td>{{ $row['sub_category'] }}</td>
            <td class="col-members">{{ $row['members'] }}</td>
            <td class="col-urgency">{{ $row['urgency'] }}</td>
            <td class="col-status">{{ $row['status'] }}</td>
            <td>{{ \Illuminate\Support\Str::limit($row['summary'], 160) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="empty">{{ __('Belum ada klaster pada periode ini.') }}</td>
        </tr>
    @endforelse
    </tbody>
</table>

</body>
</html>
