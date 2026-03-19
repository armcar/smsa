<!doctype html>
<html lang="pt">

<head>
    <meta charset="utf-8">
    <title>Evolucao Visual do Projeto SMSA</title>
    <style>
        @page {
            margin: 12mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 13px;
        }

        .slide {
            min-height: 180mm;
            page-break-after: always;
            position: relative;
            padding: 6mm;
        }

        .slide:last-child {
            page-break-after: auto;
        }

        .hero {
            background: #0f172a;
            color: #f8fafc;
            border-radius: 12px;
            padding: 10mm;
        }

        .hero h1 {
            margin: 0;
            font-size: 34px;
            line-height: 1.1;
        }

        .hero p {
            margin: 8px 0 0;
            color: #cbd5e1;
            font-size: 14px;
        }

        .hero-image {
            margin-top: 8mm;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #334155;
        }

        .hero-image img {
            width: 100%;
            display: block;
        }

        .kicker {
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #475569;
            font-size: 11px;
            margin-bottom: 6px;
        }

        h2 {
            margin: 0 0 8px;
            font-size: 26px;
        }

        .subtitle {
            color: #475569;
            margin-bottom: 10px;
        }

        .grid-2 {
            font-size: 0;
        }

        .card {
            display: inline-block;
            vertical-align: top;
            width: 47%;
            margin-right: 3%;
            margin-bottom: 10px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 10px;
            box-sizing: border-box;
            font-size: 13px;
        }

        .card:nth-child(2n) {
            margin-right: 0;
        }

        .card h3 {
            margin: 0 0 6px;
            font-size: 16px;
        }

        .card p {
            margin: 0;
            color: #475569;
            line-height: 1.45;
        }

        .metric-row {
            font-size: 0;
            margin-top: 6px;
        }

        .metric {
            display: inline-block;
            width: 23%;
            margin-right: 2%;
            border-radius: 10px;
            color: #fff;
            padding: 10px;
            box-sizing: border-box;
            font-size: 12px;
        }

        .metric:last-child {
            margin-right: 0;
        }

        .metric b {
            display: block;
            margin-top: 6px;
            font-size: 24px;
            line-height: 1;
        }

        .m1 { background: #1d4ed8; }
        .m2 { background: #0f766e; }
        .m3 { background: #7c3aed; }
        .m4 { background: #b45309; }

        .timeline {
            margin-top: 8px;
            border-left: 3px solid #cbd5e1;
            padding-left: 10px;
        }

        .timeline .item {
            margin: 0 0 10px;
        }

        .timeline .item b {
            color: #0f172a;
            display: inline-block;
            min-width: 88px;
        }

        .bar-box {
            margin: 12px 0;
        }

        .bar-label {
            font-size: 12px;
            color: #334155;
            margin-bottom: 4px;
        }

        .bar-track {
            width: 100%;
            height: 12px;
            border-radius: 999px;
            background: #e2e8f0;
        }

        .bar-fill {
            height: 12px;
            border-radius: 999px;
            background: #1d4ed8;
        }

        .footer {
            position: absolute;
            left: 6mm;
            right: 6mm;
            bottom: 2mm;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
            color: #64748b;
            font-size: 11px;
        }
    </style>
</head>

<body>
    <section class="slide">
        <div class="hero">
            <div class="kicker" style="color:#cbd5e1;">Sociedade Musical de Santo Antonio</div>
            <h1>Evolucao do Projeto SMSA</h1>
            <p>Versao executiva para direcao: progresso, impacto e proximos passos.</p>

            <div class="hero-image">
                <img src="{{ public_path('images/dashboard-capa.png') }}" alt="Painel de controlo do SMSA">
            </div>
        </div>
        <div class="footer">Data: {{ now()->format('d-m-Y') }} | Documento de apresentacao interna</div>
    </section>

    <section class="slide">
        <div class="kicker">Resumo Rapido</div>
        <h2>O que ja esta entregue</h2>
        <p class="subtitle">Sistema administrativo funcional para socios, quotas, pagamentos e recibos.</p>

        <div class="metric-row">
            <div class="metric m1">Gestao<b>Socios</b></div>
            <div class="metric m2">Tesouraria<b>Ativa</b></div>
            <div class="metric m3">Recibos<b>PDF + Email</b></div>
            <div class="metric m4">Painel<b>admin.smsa.test</b></div>
        </div>

        <div class="grid-2" style="margin-top:12px;">
            <div class="card">
                <h3>Base de dados consolidada</h3>
                <p>Entidades e relacoes alinhadas para suportar crescimento sem retrabalho estrutural.</p>
            </div>
            <div class="card">
                <h3>Fluxo de recibo completo</h3>
                <p>Do pagamento ao envio do recibo em PDF com rastreabilidade e consistencia.</p>
            </div>
            <div class="card">
                <h3>Painel com indicadores</h3>
                <p>Visao mensal de recibos e grafico de pagamentos em falta por ano.</p>
            </div>
            <div class="card">
                <h3>Configuracao centralizada</h3>
                <p>Dados institucionais movidos para ambiente/config, sem hardcode nos templates.</p>
            </div>
        </div>
        <div class="footer">Evolucao do Projeto SMSA</div>
    </section>

    <section class="slide">
        <div class="kicker">Evolucao Tecnica</div>
        <h2>Marcos do projeto</h2>
        <div class="timeline">
            <div class="item"><b>Fase 1</b> Modelacao de socios, quotas, cobrancas e pagamentos.</div>
            <div class="item"><b>Fase 2</b> Emissao de recibos PDF e envio por email.</div>
            <div class="item"><b>Fase 3</b> Dashboard com metricas e graficos de apoio a decisao.</div>
            <div class="item"><b>Fase 4</b> Ajustes de UX (loading spinner, contraste, localizacao PT).</div>
            <div class="item"><b>Fase 5</b> Separacao de dominio administrativo em admin.smsa.test.</div>
        </div>
        <div class="footer">Evolucao do Projeto SMSA</div>
    </section>

    <section class="slide">
        <div class="kicker">Impacto Operacional</div>
        <h2>Ganhos para a direcao e tesouraria</h2>
        <div class="bar-box">
            <div class="bar-label">Visibilidade de cobrancas e dividas por ano</div>
            <div class="bar-track"><div class="bar-fill" style="width: 92%;"></div></div>
        </div>
        <div class="bar-box">
            <div class="bar-label">Confianca documental (recibo PDF padronizado)</div>
            <div class="bar-track"><div class="bar-fill" style="width: 95%;"></div></div>
        </div>
        <div class="bar-box">
            <div class="bar-label">Velocidade de operacao administrativa</div>
            <div class="bar-track"><div class="bar-fill" style="width: 85%;"></div></div>
        </div>
        <div class="bar-box">
            <div class="bar-label">Preparacao para escalar funcionalidades</div>
            <div class="bar-track"><div class="bar-fill" style="width: 88%;"></div></div>
        </div>
        <div class="footer">Evolucao do Projeto SMSA</div>
    </section>

    <section class="slide">
        <div class="kicker">Roadmap Curto Prazo</div>
        <h2>Proximas entregas sugeridas</h2>
        <div class="grid-2">
            <div class="card">
                <h3>1. Relatorios executivos</h3>
                <p>Exportação mensal/anual para reunioes de direcao e balanco da tesouraria.</p>
            </div>
            <div class="card">
                <h3>2. Alertas de incumprimento</h3>
                <p>Notificacoes automatizadas para quotas em falta por janela temporal.</p>
            </div>
            <div class="card">
                <h3>3. Perfis e auditoria</h3>
                <p>Controlo de acessos por papel e trilho de alteracoes criticas.</p>
            </div>
            <div class="card">
                <h3>4. Operacao segura</h3>
                <p>Rotinas de backup e checklist de recuperacao para continuidade de servico.</p>
            </div>
        </div>
        <div class="footer">Evolucao do Projeto SMSA | Versao Direcao</div>
    </section>
</body>

</html>
