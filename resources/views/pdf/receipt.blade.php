{{-- resources/views/pdf/receipt.blade.php --}}
<!doctype html>
<html lang="pt">

<head>
    <meta charset="utf-8">
    <title>Recibo {{ $receipt->numero }}</title>

    <style>
        @page {
            margin-top: 25mm;
            margin-bottom: 25mm;
            margin-left: 20mm;
            /* 2 cm */
            margin-right: 10mm;
            /* 1 cm */
        }

        body {
            font-family: DejaVu Serif, serif;
            font-size: 12px;
            color: #111;
        }

        .header {
            width: 100%;
            border-bottom: 1px solid #ddd;
            padding-bottom: 14px;
            margin-bottom: 16px;
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
            width: 220px;
        }

        .doc-title {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: .5px;
        }

        .doc-meta {
            margin-top: 6px;
            font-size: 12px;
            line-height: 1.35;
        }

        .doc-meta b {
            font-weight: 700;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            margin: 18px 0 8px;
        }

        .info-table,
        .items-table,
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        .label {
            width: 140px;
            color: #333;
        }

        .items-table th {
            text-align: left;
            font-weight: 700;
            border-bottom: 1px solid #ddd;
            padding: 8px 6px;
        }

        .items-table td {
            border-bottom: 1px solid #f0f0f0;
            padding: 9px 6px;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .totals-wrap {
            margin-top: 10px;
            width: 100%;
        }

        .totals-table {
            width: 280px;
            margin-left: auto;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 6px 6px;
        }

        .totals-table tr.total td {
            border-top: 1px solid #ddd;
            font-weight: 700;
            font-size: 13px;
        }

        .sign {
            margin-top: 26px;
            width: 100%;
        }

        .sign-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sign-box {
            width: 50%;
            vertical-align: top;
            padding-top: 18px;
        }

        .line {
            border-top: 1px solid #333;
            width: 85%;
            margin-top: 26px;
        }

        .small {
            font-size: 10px;
            color: #555;
            margin-top: 6px;
        }

        .footer {
            position: fixed;
            bottom: 18px;
            left: 20mm;
            right: 10mm;
            font-size: 10px;
            color: #555;
            border-top: 1px solid #eee;
            padding-top: 8px;
        }

        .footer-org {
            margin-bottom: 4px;
            line-height: 1.35;
        }

        .footer-meta {
            font-size: 9px;
            color: #666;
        }
    </style>
</head>

<body>

    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo">
                    {{-- O logo já inclui o nome + “Fundada em ...”, por isso só ele --}}
                    <img src="{{ public_path('images/logo.png') }}" style="width: 320px;">
                </td>

                <td class="doc-box">
                    <div class="doc-title">RECIBO</div>
                    <div class="doc-meta">
                        <div><b>Nº:</b> {{ $receipt->numero }}</div>
                        <div><b>Data:</b> {{ \Carbon\Carbon::parse($receipt->data_pagamento)->format('d-m-Y') }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Dados do Sócio</div>
    <table class="info-table">
        <tr>
            <td class="label">Nome</td>
            <td>{{ $receipt->member->nome ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Nº Sócio</td>
            @php
                $codigo = $receipt->member->socioType->code ?? '';
                $numero = $receipt->member->num_socio ?? null;
                $numeroFormatado = $numero ? sprintf('%03d', (int) $numero) : null;
            @endphp

            <td>
                @if ($codigo && $numeroFormatado)
                    {{ $codigo }} {{ $numeroFormatado }}
                @else
                    —
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">NIF</td>
            <td>{{ $receipt->member->numero_fiscal ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Morada</td>
            <td>
                {{ $receipt->member->morada ?? '—' }}
                @if (!empty($receipt->member->codigo_postal) || !empty($receipt->member->localidade))
                    <br>
                    {{ $receipt->member->codigo_postal ?? '' }} {{ $receipt->member->localidade ?? '' }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Email</td>
            <td>{{ $receipt->member->email ?? '—' }}</td>
        </tr>
    </table>

    <div class="section-title">Detalhe</div>
    <table class="items-table">
        <thead>
            <tr>
                <th>Descrição</th>
                <th class="center" style="width: 110px;">Ano</th>
                <th class="right" style="width: 120px;">Valor</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Quota anual</td>
                <td class="center">{{ $receipt->quotaYear->ano ?? ($receipt->quota_year_id ?? '—') }}</td>
                <td class="right">{{ number_format((float) ($receipt->valor ?? 0), 2, ',', '.') }} €</td>
            </tr>
        </tbody>
    </table>

    <div class="totals-wrap">
        <table class="totals-table">
            <tr class="total">
                <td>Total</td>
                <td class="right">{{ number_format((float) ($receipt->valor ?? 0), 2, ',', '.') }} €</td>
            </tr>
        </table>
    </div>

    <div class="sign">
        <table class="sign-table">
            <tr>
                <td class="sign-box" style="width: 100%; text-align: center;">
                    <div class="line" style="margin: 26px auto 0; width: 60%;"></div>
                    <div class="small" style="text-align: center;">Tesouraria</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <div class="footer-org">
            <strong>{{ config('smsa.organization.name') }}</strong> | NIF: {{ config('smsa.organization.nif') }}<br>
            {{ config('smsa.organization.address_line') }} | {{ config('smsa.organization.postal_city') }} | email:
            {{ config('smsa.organization.email') }}
        </div>
        <div class="footer-meta">
            Documento gerado automaticamente pelo sistema SMSA. &nbsp;|&nbsp; Recibo Nº {{ $receipt->numero }}
        </div>
    </div>

</body>

</html>

