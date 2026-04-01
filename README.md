📄 CHANGELOG — TESOURARIA SMSA
🧾 Sistema de Tesouraria SMSA
Versão: 1.0 (Consolidação do núcleo financeiro)
🎯 Objetivo

Estruturar um sistema robusto para gestão de:

sócios
quotas
pagamentos
recibos
integração com inscrições (WordPress)

Garantindo:

consistência financeira
rastreabilidade documental
segurança operacional
🟢 Sprint 1 — Quotas e Pagamentos
✔️ Implementado
Cálculo correto de quotas com base em valor devido vs valor pago
Suporte a pagamentos parciais
Estado automático da quota:
pendente
parcial
pago
Recalculo automático ao:
criar pagamento
editar pagamento
anular pagamento
🎯 Resultado
Eliminação de estados incorretos
Base financeira fiável
🟢 Sprint 2 — Recibos por Pagamento
✔️ Implementado
Recibos ligados diretamente a payment_id
Valor do recibo = valor do pagamento
Suporte a múltiplos recibos no mesmo ano (pagamentos parciais)
Correção de semântica:
“Quota do ano”
remoção de “Ano de emissão” redundante
Correção de encoding (acentos em PDF)
🎯 Resultado
Rastreabilidade completa
Documentos corretos e apresentáveis
🟢 Sprint 3 — Anulação Consistente
✔️ Implementado
Anulação de pagamento por anulado_em
Anulação automática do recibo associado
Registo de motivo_anulacao
Recalculo automático da quota
Bloqueio de ações inválidas na UI
PDF com marca “ANULADO”
🎯 Resultado
Sistema auditável
Sem eliminação de histórico
Consistência documental garantida
🟢 Sprint 4 — Integração WP + Automação
✔️ Implementado
Endpoint WP com:
idempotência (dedupe)
proteção contra submissões duplicadas
Numeração automática de sócio (sem placeholders)
Comando anual de geração de quotas
Execução idempotente (sem duplicação)
Scheduler configurado
🎯 Resultado
Integração externa robusta
Automação segura
Redução de erros operacionais
🟢 Sprint 5 — Testes Automáticos
✔️ Implementado
Suite de testes (Feature) para:
quotas e pagamentos
recibos
anulações
integração WP
comando anual
numeração de sócios
Factories para modelos principais
Execução com RefreshDatabase
📊 Resultado
19 testes a passar
Cobertura dos fluxos críticos
Base segura para evolução futura
🔐 Garantias do Sistema

O sistema garante:

✔️ Consistência financeira
estados calculados automaticamente
pagamentos anulados não contam
✔️ Rastreabilidade
recibos ligados a pagamentos
histórico preservado
✔️ Auditabilidade
anulações registadas
documentos marcados como anulados
✔️ Robustez operacional
dedupe na integração WP
automação anual sem duplicação
✔️ Segurança evolutiva
testes automáticos previnem regressões
⚠️ Notas Técnicas
Ajustes feitos para compatibilidade com SQLite (testes)
Sistema preparado para MySQL em produção
Recibos antigos continuam suportados
🚀 Próximos Passos (não críticos)
controlo de permissões (RBAC com Spatie)
melhoria de layout de recibos (assinaturas, branding)
notificações automáticas (email de quotas/atrasos)
dashboard executivo para Direção
🏁 Conclusão

O sistema de tesouraria atingiu um estado:

👉 funcional, consistente e auditável

Pronto para:

utilização real
demonstração institucional
evolução controlada

---

Se um sócio já tem movimentos ou documentos, não deve poder ser eliminado. Deve ser inativado.

Isto protege:

quotas
pagamentos
recibos
histórico
auditoria
🎯 Objetivo desta fase

Implementar um comportamento seguro para o ciclo de vida do sócio:

Regra desejada
Sócio sem movimentos → pode ser eliminado
Sócio com movimentos/documentos → não pode ser eliminado; deve ser inativado

🧭 O que eu recomendo tecnicamente

1. Não apagar histórico

Nada de delete para sócios com:

quota_charges
payments indiretos via quotas
receipts 2. Ter estado de atividade

O ideal é usar um campo simples, se já não existir:

ativo boolean
ou
estado com ativo/inativo

Pelo teu projeto, ativo boolean parece-me o mais direto e limpo.

3. UI no Filament

Em SocioResource:

esconder ou bloquear ação Delete quando o sócio tem movimentos
criar ação Inativar
opcionalmente ação Reativar
mostrar badge de estado 4. Listagens

Poder:

ver ativos
ver inativos
filtrar por estado

1. Modelo de Sócio (Laravel)
   Referências principais:

app/Models/Socio.php

app/Models/SocioType.php

database/migrations/2026_02_16_132512_create_socios_table.php

database/migrations/2026_03_24_120000_add_wp_user_id_to_socios_table.php

Socio é o agregado principal.

Campos-chave: socio_type_id, num_socio (int), nome, email, numero_fiscal, estado (ativo/suspenso), data_socio, wp_user_id, campos de instrumento.

Regras relevantes:

Normaliza email e NIF no saving.

Ao apagar sócio: bloqueia se houver movimentos (quotas/pagamentos/recibos).

Ao mudar estado: sincroniza role no WordPress (adiciona/remove role de sócio).

Relações:

Tipo de Sócio: socioType() (belongsTo SocioType).

Quotas: quotaCharges() (hasMany QuotaCharge).

Pagamentos: payments() (hasManyThrough Payment via QuotaCharge).

Recibos: receipts() (hasMany Receipt via member_id).

Ligação WordPress: wp_user_id único em socios.

2. Tratamento de Sócios (WordPress)
   Referências:

wp-content/mu-plugins/smsa-reserved-area.php

wp-content/mu-plugins/smsa-inscricoes/includes/forms.php

Representação:

Utilizadores em wp_users.

Role de sócio é smsa_socio (não socio).

Role/capability fica em wp_usermeta (wp_capabilities).

Controlo de acesso:

Admin bar escondida para não-staff (show_admin_bar).

Backoffice bloqueado para não-staff (admin_init redireciona).

Área /area-reservada/... protegida por template_redirect.

Utilizador sócio só entra na folha correta (/area-reservada/socios/).

Lógica personalizada relevante:

smsa-reserved-area (acesso, perfil, consumo API Laravel, placeholders).

smsa-inscricoes (formulários de inscrição/atualização, sync com Laravel, callback de status).

3. Integração entre sistemas
   Referências:

routes/web.php
app/Http/Controllers/WpApplicationIngestController.php
app/Filament/Resources/WpApplicationResource/Pages/EditWpApplication.php
app/Services/WordPressUserProvisioner.php
wp-content/mu-plugins/smsa-inscricoes/includes/laravel-sync.php
Como um Sócio no Laravel vira utilizador WordPress:

WP envia inscrição para POST /integrations/wp/applications com token.
Laravel cria/atualiza wp_applications (estado inicial pendente).
Backoffice Laravel (Filament) muda para validada.
tryAutoCreateSocioOnValidation() cria Socio (se ainda não existir).
WordPressUserProvisioner::createMemberUser() cria/associa wp_users e grava socios.wp_user_id.
Laravel envia callback de estado para WP (/wp-json/smsa/v1/application-status).
Username/password:

Username: socio-<TIPO><NUMERO_PADDED> (ex. socio-B023).
Password: aleatória, hash bcrypt gravada em wp_users.user_pass.
Em produção, password em claro não é exposta pelo Laravel (só em local/dev, opcional).
Sincronização:

Não é bidirecional completa.
Fluxo principal é WP -> Laravel (inscrição) + Laravel -> WP (criação user/role/status callback).
Criação de user WP é automática após validação no Laravel.
Atualização de dados por sócio logado no WP: entra no Laravel e pode atualizar Socio imediatamente por wp_user_id. 4) Ciclo de vida do Sócio (End-to-End)
Inscrição (onde começa?)
Implementado ✅
Começa no WP (smsa_inscricao_socio).
Criação do Sócio (Laravel)
Parcial ⚠️
Só acontece quando pedido é validado no backoffice Laravel.
Criação do utilizador no WordPress
Parcial ⚠️
Automática após validação no Laravel; depende desse passo manual e pode falhar por conflitos de email/username.
Login no WordPress
Implementado ✅
Fluxo WP normal + redirecionamentos para área reservada.
Acesso à área de sócio
Implementado ✅
Restrições por role smsa_socio e rota /area-reservada/socios/.
Visualização de dados pessoais
Parcial ⚠️
Financeiro vem da API Laravel (bom).
Dados pessoais no card vêm sobretudo de tabela WP de inscrições/fallback, não diretamente da API oficial de sócio. 5) Fluxo de dados
Fonte de verdade operacional de sócio/quotas/pagamentos/recibos: Laravel.
Dados também guardados no WP:
wp_users/wp_usermeta (conta/roles).
wp_smsa_inscricoes_socios (staging/histórico de formulário).
Disponibilização ao WP:
API assinada HMAC: GET /api/member-area/me (financeiro + recibos).
Callback Laravel -> WP de estado de inscrição.
Também existe acesso direto SQL do WP a tabelas Laravel (wp_applications, socios) no smsa-reserved-area (acoplamento forte). 6) Inconsistências / Riscos
Dados duplicados entre Laravel e WP (inscrição + sócio final).
Acoplamento por BD partilhada: WP consulta tabelas Laravel diretamente.
Processo de validação ainda dependente de ação manual no Laravel.
Gestão de credenciais sensível:
token de integração está definido em wp-config.php (risco de exposição se versionado).
Password inicial do utilizador WP não tem fluxo claro de entrega em produção.
Sincronização incompleta:
alterações de perfil no Laravel não refletem automaticamente em wp_users (ex. email/display_name).
Naming inconsistente (smsa_socio, member_id em recibos, num_socio int vs comentários com formato textual). 7) Estado Atual
Estado da integração: Parcial (avançado).
Nível de confiança: Médio-Alto (baseado em leitura direta do código dos dois repositórios). 8) Próximos passos (máx. 5)
Definir Laravel como fonte única também para dados pessoais na área reservada (eliminar fallback por tabela WP de inscrições).
Fechar fluxo de credenciais em produção (reset password obrigatório no 1.º acesso ou email transacional seguro).
Remover consultas SQL diretas do WP a tabelas Laravel e usar apenas API autenticada.
Automatizar ou simplificar a validação (pendente -> validada) com regras explícitas para reduzir operação manual.
Rotacionar e externalizar segredos (SMSA_LARAVEL_BRIDGE_TOKEN/secret) fora do código e rever política de callbacks/HMAC.
