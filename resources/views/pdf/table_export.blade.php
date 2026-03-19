<!doctype html>
<html lang="pt">

<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>

    <style>
        @page {
            size: A4 landscape;
            margin-top: 25mm;
            margin-bottom: 10mm;
            margin-left: 20mm;
            margin-right: 10mm;
        }

        body {
            font-family: DejaVu Serif, serif;
            font-size: 9.5px;
            color: #111;
        }

        .header {
            width: 100%;
            border-bottom: 1px solid #ddd;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo {
            width: 320px;
            vertical-align: top;
        }

        .doc-box {
            text-align: right;
            vertical-align: top;
        }

        .doc-title {
            font-size: 14px;
            font-weight: 700;
        }

        .doc-meta {
            margin-top: 6px;
            font-size: 9px;
            color: #444;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
        }

        table.data th {
            text-align: left;
            padding: 4px 5px;
            border-bottom: 1px solid #ddd;
            background: #f6f6f6;
            font-weight: 700;
        }

        table.data td {
            padding: 3px 5px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
        }

        .footer {
            position: fixed;
            bottom: 2mm;
            left: 20mm;
            right: 10mm;
            font-size: 9px;
            color: #555;
            border-top: 1px solid #eee;
            padding-top: 4px;
        }

        .footer-org {
            margin-bottom: 2px;
            line-height: 1.35;
        }

        .footer-meta {
            font-size: 8px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo">
                    <img src="{{ public_path('images/logo.png') }}" style="width: 320px;">
                </td>
                <td class="doc-box">
                    <div class="doc-title">{{ $title }}</div>
                    <div class="doc-meta">Gerado em {{ $generatedAt }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}">Sem registos para exportar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div class="footer-org">
            <strong>{{ $orgName }}</strong> | NIF: {{ $orgNif }}<br>
            {{ $orgAddressLine }} | {{ $orgPostalCity }} | email:
            {{ $orgEmail }}
        </div>
        <div class="footer-meta">
            Documento gerado automaticamente pelo sistema SMSA.
        </div>
    </div>
</body>

</html>
