<!doctype html>
<html lang="pt">

<head>
    <meta charset="utf-8">
    <title>Evolução do Projeto SMSA</title>
    <style>
        @page {
            margin: 16mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 14px;
        }

        .slide {
            min-height: 170mm;
            padding: 8mm 6mm;
            page-break-after: always;
            position: relative;
        }

        .slide:last-child {
            page-break-after: auto;
        }

        .kicker {
            font-size: 11px;
            color: #475569;
            letter-spacing: .4px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        h1 {
            margin: 0 0 12px;
            font-size: 32px;
            line-height: 1.15;
        }

        h2 {
            margin: 0 0 12px;
            font-size: 24px;
            line-height: 1.2;
        }

        p {
            margin: 0 0 10px;
            line-height: 1.5;
        }

        ul {
            margin: 8px 0 0 16px;
            padding: 0;
        }

        li {
            margin: 0 0 8px;
            line-height: 1.45;
        }

        .muted {
            color: #64748b;
        }

        .tag {
            display: inline-block;
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 999px;
            background: #e2e8f0;
            color: #0f172a;
            margin-right: 6px;
        }

        .cover-image {
            margin-top: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
        }

        .cover-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        .footer {
            position: absolute;
            left: 6mm;
            right: 6mm;
            bottom: 3mm;
            font-size: 11px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
    </style>
</head>

<body>
    <section class="slide">
        <div class="kicker">Sociedade Musical de Santo António</div>
        <h1>Evolução do Projeto SMSA</h1>
        <p class="muted">Resumo executivo da evolução funcional e técnica do sistema administrativo.</p>
        <p><span class="tag">Laravel 12</span><span class="tag">Filament 3</span><span class="tag">MySQL</span><span class="tag">PDF + Email</span></p>
        <div class="cover-image">
            <img src="{{ public_path('images/dashboard-capa.png') }}" alt="Painel de controlo do projeto SMSA">
        </div>
        <div class="footer">Data: {{ now()->format('d-m-Y') }} | Projeto SMSA</div>
    </section>

    <section class="slide">
        <div class="kicker">1. Objetivo</div>
        <h2>Digitalizar gestão de sócios, quotas e recibos</h2>
        <ul>
            <li>Centralizar cadastro de sócios e tipologias de sócio.</li>
            <li>Controlar quotas anuais, cobranças e pagamentos com histórico.</li>
            <li>Emitir recibos PDF e envio automático por email.</li>
            <li>Disponibilizar dashboard com indicadores operacionais.</li>
        </ul>
        <div class="footer">Evolução do Projeto SMSA</div>
    </section>

    <section class="slide">
        <div class="kicker">2. Estrutura de Dados</div>
        <h2>Modelo consolidado de faturação de quotas</h2>
        <ul>
            <li><strong>Socios</strong>, <strong>SocioTypes</strong> e <strong>QuotaYears</strong> como base mestre.</li>
            <li><strong>QuotaCharges</strong> para lançamentos anuais por sócio (estado e valor).</li>
            <li><strong>Payments</strong> com suporte a pagamentos múltiplos e anulação.</li>
            <li><strong>Receipts</strong> para emissão formal e rastreabilidade documental.</li>
        </ul>
        <div class="footer">Evolução do Projeto SMSA</div>
    </section>

    <section class="slide">
        <div class="kicker">3. Fluxo de Negócio</div>
        <h2>Do pagamento ao recibo</h2>
        <ul>
            <li>Registo do pagamento e associação à quota/cobrança.</li>
            <li>Emissão de recibo com número e data de pagamento.</li>
            <li>Geração de PDF e envio por email ao sócio.</li>
            <li>Ações rápidas no painel para download e reenvio do recibo.</li>
        </ul>
        <div class="footer">Evolução do Projeto SMSA</div>
    </section>

    <section class="slide">
        <div class="kicker">4. Recibo PDF</div>
        <h2>Melhorias visuais e institucionais</h2>
        <ul>
            <li>Template de recibo ajustado para leitura clara e assinatura de tesouraria.</li>
            <li>Rodapé com dados institucionais completos da associação.</li>
            <li>Correção de encoding UTF-8 (acentos, símbolo do euro e textos PT).</li>
            <li>Dados institucionais extraídos de configuração/ambiente (sem hardcode).</li>
        </ul>
        <div class="footer">Evolução do Projeto SMSA</div>
    </section>

    <section class="slide">
        <div class="kicker">5. Experiência no Painel</div>
        <h2>Interação mais estável e legível</h2>
        <ul>
            <li>Spinner de loading melhorado para evitar flicker.</li>
            <li>Tempo mínimo de visibilidade e melhor sincronização entre requests.</li>
            <li>Ajustes de contraste e consistência visual no painel.</li>
            <li>Localização de data em português aplicada no datepicker.</li>
        </ul>
        <div class="footer">Evolução do Projeto SMSA</div>
    </section>

    <section class="slide">
        <div class="kicker">6. Dashboard</div>
        <h2>Indicadores e suporte à decisão</h2>
        <ul>
            <li>Estatísticas de recibos e totais anuais/acumulados.</li>
            <li>Gráfico Recibos por mês (ano atual).</li>
            <li>Novo gráfico Pagamentos em falta (valores por ano).</li>
            <li>Diferenciação visual por cor para leitura rápida dos contextos.</li>
        </ul>
        <div class="footer">Evolução do Projeto SMSA</div>
    </section>

    <section class="slide">
        <div class="kicker">7. Domínio e Acesso</div>
        <h2>Separação do painel administrativo</h2>
        <ul>
            <li>Alteração da rota de <strong>smsa.test/admin</strong> para <strong>admin.smsa.test</strong>.</li>
            <li>Configuração de domínio dedicado no painel Filament.</li>
            <li>Ajustes de ambiente (`APP_URL`, `FILAMENT_DOMAIN`).</li>
            <li>Compatibilização com vhost/hosts local no Laragon.</li>
        </ul>
        <div class="footer">Evolução do Projeto SMSA</div>
    </section>

    <section class="slide">
        <div class="kicker">8. Qualidade Técnica</div>
        <h2>Base mais segura para evolução contínua</h2>
        <ul>
            <li>Validações de sintaxe e limpeza de cache de configuração.</li>
            <li>Padronização de textos e encoding em templates críticos.</li>
            <li>Configuração centralizada para dados institucionais.</li>
            <li>Manutenção orientada a pequenas melhorias incrementais.</li>
        </ul>
        <div class="footer">Evolução do Projeto SMSA</div>
    </section>

    <section class="slide">
        <div class="kicker">9. Próximos Passos</div>
        <h2>Roadmap recomendado</h2>
        <ul>
            <li>Exportações gerenciais (mensal/anual) para tesouraria.</li>
            <li>Perfis de acesso e auditoria de alterações no painel.</li>
            <li>Notificações automáticas de quotas em atraso.</li>
            <li>Automação de cópias de segurança e monitorização básica.</li>
        </ul>
        <p class="muted">Documento preparado para apresentação interna da direção.</p>
        <div class="footer">Evolução do Projeto SMSA</div>
    </section>
</body>

</html>
